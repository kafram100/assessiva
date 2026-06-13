<?php

namespace App\Services;

use App\Data\AnswerMarkingEvaluation;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Support\TheoryMarkingFairnessGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScriptMarkingApiService
{
    /**
     * Backwards-compatible: returns marks only.
     */
    public function markAnswer(Question $question, string $studentResponse, string $questionType = 'theory'): ?float
    {
        $ev = $this->evaluateAnswer($question, $studentResponse, $questionType);

        return $ev?->marksAwarded;
    }

    /**
     * Full structured evaluation (theory / fill-in when API used).
     */
    public function evaluateAnswer(Question $question, string $studentResponse, string $questionType): ?AnswerMarkingEvaluation
    {
        $questionType = $questionType === 'short_answer' ? 'theory' : $questionType;

        $apiConfig = config('marking.api');
        if (empty($apiConfig['enabled'])) {
            $this->logPipeline('evaluateAnswer skipped: marking.api.enabled is false', [
                'question_id' => $question->id,
                'question_type' => $questionType,
            ]);

            return null;
        }

        $maxMarks = (int) ($question->marks ?? 0);
        if ($maxMarks <= 0) {
            return new AnswerMarkingEvaluation(
                marksAwarded: 0.0,
                confidence: null,
                evaluationStatus: 'n/a',
                detectedConcepts: [],
                missingConcepts: [],
                reason: 'Question has zero marks.',
                feedback: null,
                rawModelResponse: null,
                conceptCoverage: null,
                rubricConcepts: [],
            );
        }

        $driver = config('marking.driver', 'external');
        $this->logPipeline('evaluateAnswer dispatch', [
            'question_id' => $question->id,
            'question_type' => $questionType,
            'driver' => $driver,
            'external_url' => $this->resolveExternalMarkUrl(),
            'max_marks' => $maxMarks,
            'student_response_len' => mb_strlen($studentResponse),
        ]);

        if ($driver === 'external') {
            return $this->evaluateViaExternalApi($question, $studentResponse, $maxMarks, $questionType);
        }

        if ($driver === 'gemini') {
            return $this->evaluateViaGemini($question, $studentResponse, $maxMarks, $questionType);
        }

        if ($driver === 'openai') {
            return $this->evaluateViaOpenAi($question, $studentResponse, $maxMarks, $questionType);
        }

        return $this->evaluateViaExternalApi($question, $studentResponse, $maxMarks, $questionType);
    }

    /**
     * Resolve FastAPI mark-answer endpoint from external_url / AI_SERVICE_URL.
     */
    public function resolveExternalMarkUrl(): ?string
    {
        $base = trim((string) (
            config('marking.api.external_url')
            ?: config('marking.api.url')
            ?: config('services.ai.url')
            ?: ''
        ));

        if ($base === '') {
            return null;
        }

        $base = rtrim($base, '/');
        if (str_ends_with(strtolower($base), '/mark-answer')) {
            return $base;
        }

        return $base.'/mark-answer';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logPipeline(string $message, array $context = []): void
    {
        if (! config('marking.api.log_pipeline', false)) {
            return;
        }

        Log::info('[script_marking] '.$message, $context);
    }

    protected function evaluateViaGemini(Question $question, string $studentResponse, int $maxMarks, string $questionType): ?AnswerMarkingEvaluation
    {
        $gemini = config('marking.gemini');
        if (empty($gemini['key'])) {
            Log::warning('Script marking: GEMINI_API_KEY not set');
            $this->logPipeline('Gemini aborted: missing API key', ['question_id' => $question->id]);

            return null;
        }

        $prompt = $this->buildJsonEvaluationPrompt($question, $studentResponse, $maxMarks, $questionType);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $questionType === 'theory' ? 0.35 : 0.2,
                'maxOutputTokens' => 800,
            ],
        ];

        $timeout = config('marking.api.timeout', 30);
        $verify = $gemini['verify_ssl'] ?? true;
        $model = $gemini['model'] ?? 'gemini-1.5-flash';

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $request = Http::timeout($timeout);
            if (! $verify) {
                $request = $request->withOptions(['verify' => false]);
            }

            $response = $request->post($url.'?key='.urlencode($gemini['key']), $payload);

            if (! $response->successful()) {
                $body = $response->json();
                $msg = $body['error']['message'] ?? $response->body();
                Log::warning('Gemini marking API error', [
                    'status' => $response->status(),
                    'message' => $msg,
                ]);
                $this->logPipeline('Gemini HTTP error', [
                    'question_id' => $question->id,
                    'status' => $response->status(),
                    'message' => is_string($msg) ? mb_substr($msg, 0, 500) : null,
                ]);

                return null;
            }

            $data = $response->json();
            $text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $this->logPipeline('Gemini raw model text', [
                'question_id' => $question->id,
                'text_len' => mb_strlen($text),
                'text_preview' => mb_substr($text, 0, 2000),
            ]);

            $ev = $this->parseEvaluationText($text, $maxMarks);
            $this->logPipeline('Gemini parsed evaluation', [
                'question_id' => $question->id,
                'marks_awarded' => $ev->marksAwarded,
                'evaluation_status' => $ev->evaluationStatus,
                'detected_concepts' => $ev->detectedConcepts,
                'missing_concepts' => $ev->missingConcepts,
            ]);

            return $questionType === 'theory'
                ? TheoryMarkingFairnessGuard::apply($ev, $maxMarks, $studentResponse, $questionType)
                : $ev;
        } catch (\Throwable $e) {
            Log::error('Gemini marking exception', ['message' => $e->getMessage()]);
            $this->logPipeline('Gemini exception', [
                'question_id' => $question->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function evaluateViaOpenAi(Question $question, string $studentResponse, int $maxMarks, string $questionType): ?AnswerMarkingEvaluation
    {
        $openai = config('marking.openai');
        if (empty($openai['key'])) {
            Log::warning('Script marking: OPENAI_API_KEY not set');
            $this->logPipeline('OpenAI aborted: missing API key', ['question_id' => $question->id]);

            return null;
        }

        $prompt = $this->buildJsonEvaluationPrompt($question, $studentResponse, $maxMarks, $questionType);

        $system = $questionType === 'theory'
            ? 'You are a fair university examiner marking THEORY answers. Reward conceptual understanding and synonyms; do not require exact wording of the model answer. Reply with ONE JSON object only (no markdown fences), using exactly the keys requested in the user message.'
            : 'You are an exam marker. Reply with a single JSON object only (no markdown fences), using exactly the keys requested in the user message.';

        $payload = [
            'model' => $openai['model'] ?? 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $questionType === 'theory' ? 0.35 : 0.2,
            'max_tokens' => 800,
        ];

        $timeout = config('marking.api.timeout', 30);
        $verify = $openai['verify_ssl'] ?? true;

        try {
            $request = Http::withToken($openai['key'])->timeout($timeout);
            if (! $verify) {
                $request = $request->withOptions(['verify' => false]);
            }
            $response = $request->post('https://api.openai.com/v1/chat/completions', $payload);

            if (! $response->successful()) {
                $body = $response->json();
                $msg = $body['error']['message'] ?? $response->body();
                Log::warning('OpenAI marking API error', [
                    'status' => $response->status(),
                    'message' => $msg,
                ]);
                $this->logPipeline('OpenAI HTTP error', [
                    'question_id' => $question->id,
                    'status' => $response->status(),
                    'message' => is_string($msg) ? mb_substr($msg, 0, 500) : null,
                ]);

                return null;
            }

            $data = $response->json();
            $content = trim($data['choices'][0]['message']['content'] ?? '');
            $this->logPipeline('OpenAI raw model text', [
                'question_id' => $question->id,
                'text_len' => mb_strlen($content),
                'text_preview' => mb_substr($content, 0, 2000),
            ]);

            $ev = $this->parseEvaluationText($content, $maxMarks);
            $this->logPipeline('OpenAI parsed evaluation', [
                'question_id' => $question->id,
                'marks_awarded' => $ev->marksAwarded,
                'evaluation_status' => $ev->evaluationStatus,
                'detected_concepts' => $ev->detectedConcepts,
                'missing_concepts' => $ev->missingConcepts,
            ]);

            return $questionType === 'theory'
                ? TheoryMarkingFairnessGuard::apply($ev, $maxMarks, $studentResponse, $questionType)
                : $ev;
        } catch (\Throwable $e) {
            Log::error('OpenAI marking exception', ['message' => $e->getMessage()]);
            $this->logPipeline('OpenAI exception', [
                'question_id' => $question->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function evaluateViaExternalApi(Question $question, string $studentResponse, int $maxMarks, string $questionType): ?AnswerMarkingEvaluation
    {
        $url = $this->resolveExternalMarkUrl();
        if ($url === null) {
            $this->logPipeline('External marking aborted: missing SCRIPT_MARKING_API_URL / AI_SERVICE_URL', [
                'question_id' => $question->id,
            ]);

            return null;
        }

        $config = config('marking.api');
        $qTypeRaw = $question->q_type instanceof QuestionType ? $question->q_type->value : (string) $question->q_type;

        $payload = [
            'question_text' => $question->question_text,
            'student_answer' => $studentResponse,
            'student_response' => $studentResponse,
            'max_marks' => $maxMarks,
            'question_type' => $questionType === 'theory' ? 'theory' : $qTypeRaw,
            'evaluation_format' => 'json_v1',
        ];
        if ($question->correct_option !== null && $question->correct_option !== '') {
            $payload['model_answer'] = $question->correct_option;
        }

        $this->logPipeline('External marking request', [
            'question_id' => $question->id,
            'url' => $url,
            'payload' => [
                'question_type' => $payload['question_type'],
                'max_marks' => $maxMarks,
                'student_answer_len' => mb_strlen($studentResponse),
                'has_model_answer' => isset($payload['model_answer']),
            ],
        ]);

        $request = Http::timeout($config['timeout'] ?? 120);
        if (! empty($config['key'])) {
            $request = $request->withHeaders(['Authorization' => 'Bearer '.$config['key']]);
        }

        try {
            $response = $request->post($url, $payload);

            $this->logPipeline('External marking response', [
                'question_id' => $question->id,
                'status' => $response->status(),
                'body_preview' => mb_substr($response->body(), 0, 2000),
            ]);

            if (! $response->successful()) {
                Log::warning('Script marking API error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->logPipeline('External marking HTTP error', [
                    'question_id' => $question->id,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data)) {
                $this->logPipeline('External marking invalid JSON body', ['question_id' => $question->id]);

                return null;
            }

            if (isset($data['marks_awarded']) || isset($data['confidence']) || isset($data['evaluation_status'])) {
                $ev = AnswerMarkingEvaluation::fromArray($data, $maxMarks, json_encode($data));
                $this->logPipeline('External marking parsed evaluation', [
                    'question_id' => $question->id,
                    'marks_awarded' => $ev->marksAwarded,
                    'evaluation_status' => $ev->evaluationStatus,
                    'detected_concepts' => $ev->detectedConcepts,
                    'missing_concepts' => $ev->missingConcepts,
                    'concept_coverage' => $ev->conceptCoverage,
                ]);

                return $questionType === 'theory'
                    ? TheoryMarkingFairnessGuard::apply($ev, $maxMarks, $studentResponse, $questionType)
                    : $ev;
            }

            $marks = (float) ($data['marks_awarded'] ?? $data['score'] ?? 0);

            return new AnswerMarkingEvaluation(
                marksAwarded: max(0.0, min((float) $maxMarks, $marks)),
                confidence: isset($data['confidence']) ? (float) $data['confidence'] : null,
                evaluationStatus: isset($data['evaluation_status']) ? (string) $data['evaluation_status'] : null,
                detectedConcepts: [],
                missingConcepts: [],
                reason: isset($data['reason']) ? (string) $data['reason'] : null,
                feedback: isset($data['feedback']) ? (string) $data['feedback'] : null,
                rawModelResponse: json_encode($data),
                conceptCoverage: null,
                rubricConcepts: [],
            );
        } catch (\Throwable $e) {
            Log::error('Script marking API exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            $this->logPipeline('External marking exception', [
                'question_id' => $question->id,
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function buildJsonEvaluationPrompt(Question $question, string $studentResponse, int $maxMarks, string $questionType): string
    {
        $modelAnswer = $question->correct_option !== null && $question->correct_option !== ''
            ? "\nExpected / model answer (reference): ".$question->correct_option
            : '';

        if ($questionType === 'fill_in') {
            return <<<TXT
You are an exam marker. This is a SHORT fill-in answer; award full marks only for answers that match the expected meaning; allow minor wording differences.

Question: {$question->question_text}
Maximum marks: {$maxMarks}
{$modelAnswer}

Student response:
{$studentResponse}

Reply with ONE JSON object only (no markdown code fences), using these keys:
- "marks_awarded" (number, 0 to {$maxMarks}, can be fractional with one decimal)
- "confidence" (number 0 to 1, your certainty in the mark)
- "evaluation_status": one of "correct", "partial", "incorrect", "unanswered"
- "detected_concepts": array of short strings (key ideas the student got right)
- "missing_concepts": array of short strings (important ideas missing or wrong)
- "reason": one or two sentences explaining the mark
- "feedback": one sentence advice for the student

If the response is blank or irrelevant, use marks_awarded 0 and evaluation_status "unanswered".
TXT;
        }

        return <<<TXT
You are a fair university examiner marking a THEORY answer. Your job is CONCEPT COVERAGE, not matching exact sentences.

Rules:
1. Infer 4–8 distinct key concepts a strong answer should cover (from the question and the reference answer). List them mentally, then output them in "key_concepts_rubric" as short phrases (the rubric you used).
2. Treat synonyms, paraphrases, and informal wording as valid when the student clearly shows understanding of a concept.
3. Award "marks_awarded" proportionally to how much of the intended conceptual ground is covered (0 to {$maxMarks}). Use 0.5 mark steps (e.g. 0.5, 1, 1.5, 2).
4. Set "concept_coverage" to a number between 0 and 1 (fraction of rubric concepts adequately addressed).
5. Use "evaluation_status" "partial" when understanding is real but incomplete. Use "correct" only when coverage is essentially complete. Use "incorrect" only when the answer is mostly irrelevant or wrong. Use "unanswered" only when blank or nonsense.
6. CRITICAL FAIRNESS: Do NOT give marks_awarded = 0 if the student demonstrates at least one clearly relevant concept in "detected_concepts" (unless the answer is off-topic). Minimum partial credit in that case should be at least min(0.5, {$maxMarks} * 0.25), unless {$maxMarks} is below 0.25.
7. "missing_concepts" should list rubric ideas not adequately shown in the student's answer.

Question: {$question->question_text}
Maximum marks: {$maxMarks}
{$modelAnswer}

Student response:
{$studentResponse}

Reply with ONE JSON object only (no markdown code fences), using these keys:
- "marks_awarded" (number, 0 to {$maxMarks}, step 0.5)
- "confidence" (number 0 to 1)
- "concept_coverage" (number 0 to 1)
- "evaluation_status": one of "correct", "partial", "incorrect", "unanswered"
- "key_concepts_rubric": array of short strings (the concepts you judged against)
- "detected_concepts": array of short strings (ideas the student demonstrated)
- "missing_concepts": array of short strings (important gaps)
- "reason": one or two sentences explaining the mark and coverage
- "feedback": one sentence advice for improvement

If the response is blank or completely unrelated to the question, use marks_awarded 0, concept_coverage 0, evaluation_status "unanswered", and empty arrays where appropriate.
TXT;
    }

    protected function parseEvaluationText(string $text, int $maxMarks): AnswerMarkingEvaluation
    {
        $decoded = $this->extractJsonObject($text);
        if (is_array($decoded)) {
            return AnswerMarkingEvaluation::fromArray($decoded, $maxMarks, $text);
        }

        preg_match('/-?\d+(?:\.\d+)?/', $text, $m);
        $marks = isset($m[0]) ? (float) $m[0] : 0.0;
        $marks = max(0.0, min((float) $maxMarks, $marks));

        return new AnswerMarkingEvaluation(
            marksAwarded: $marks,
            confidence: null,
            evaluationStatus: 'unknown',
            detectedConcepts: [],
            missingConcepts: [],
            reason: 'Model did not return JSON; parsed numeric fallback.',
            feedback: null,
            rawModelResponse: $text,
            conceptCoverage: null,
            rubricConcepts: [],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractJsonObject(string $text): ?array
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }
        if (preg_match('/\{[\s\S]*\}/', $t, $m)) {
            $json = $m[0];
            $data = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    /** Check if marking is enabled and should run for this question type. */
    public function shouldMarkByApi(string $questionType): bool
    {
        $config = config('marking.api');
        if (empty($config['enabled'])) {
            return false;
        }

        $driver = config('marking.driver', 'external');

        if ($driver === 'external') {
            if ($this->resolveExternalMarkUrl() === null) {
                return false;
            }
        } elseif ($driver === 'gemini') {
            if (empty(config('marking.gemini.key'))) {
                return false;
            }
        } elseif ($driver === 'openai') {
            if (empty(config('marking.openai.key'))) {
                return false;
            }
        } else {
            if ($this->resolveExternalMarkUrl() === null) {
                return false;
            }
        }

        if ($questionType === 'theory' || $questionType === 'short_answer') {
            return true;
        }
        if ($questionType === 'fill_in') {
            return ! empty($config['use_for_fill_in']);
        }

        return false;
    }
}
