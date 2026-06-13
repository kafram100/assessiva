<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamFairnessReport;
use App\Models\Question;
use Illuminate\Support\Facades\Log;

class ExamFairnessService
{
    public function __construct(
        private readonly AiService $ai,
    ) {}

    public function fingerprint(Exam $exam): string
    {
        $exam->loadMissing(['questions' => fn ($q) => $q->orderBy('id')]);
        $parts = [];
        foreach ($exam->questions as $q) {
            $parts[] = [
                'id' => $q->id,
                'text' => (string) $q->question_text,
                'marks' => (int) $q->marks,
                'type' => $q->q_type->value,
            ];
        }

        return hash('sha256', json_encode($parts));
    }

    /**
     * @return list<string>
     */
    public function topicsFromCourse(?Course $course): array
    {
        if (! $course || ! is_string($course->description) || trim($course->description) === '') {
            return [];
        }
        $lines = preg_split('/\R/u', $course->description) ?: [];
        $topics = [];
        foreach ($lines as $line) {
            $t = trim($line, " \t-*•0123456789.");
            if ($t !== '' && strlen($t) < 200) {
                $topics[] = $t;
            }
            if (count($topics) >= 25) {
                break;
            }
        }

        return $topics;
    }

    /**
     * @return array{ok: bool, message: ?string, skipped?: bool}
     */
    public function runAnalysis(Exam $exam, bool $force): array
    {
        $exam->load(['questions' => fn ($q) => $q->orderBy('id'), 'course']);

        if ($exam->questions->isEmpty()) {
            return ['ok' => false, 'message' => 'Add questions before running fairness analysis.'];
        }

        $fp = $this->fingerprint($exam);
        $existing = $exam->fairnessReport;

        if (! $force && $existing && $existing->questions_fingerprint === $fp) {
            return ['ok' => true, 'message' => null, 'skipped' => true];
        }

        $limit = max(30, (int) config('services.ai.sync_max_execution', 600));
        set_time_limit($limit);

        $topics = $this->topicsFromCourse($exam->course);
        $payload = $this->buildFastApiPayload($exam, $topics);

        $result = $this->ai->postJsonOrError('analyze-exam-fairness', $payload);

        if (! $result['ok']) {
            return ['ok' => false, 'message' => $this->humanizeAiError((string) ($result['error'] ?? 'Unknown error'))];
        }

        $data = $result['data'] ?? null;
        if (! is_array($data)) {
            Log::error('Exam fairness: invalid AI payload shape');

            return ['ok' => false, 'message' => 'Fairness analysis could not be completed. Please try again.'];
        }

        if (empty($data['success'])) {
            $err = isset($data['error']) && is_string($data['error']) ? $data['error'] : 'AI returned an error.';

            return ['ok' => false, 'message' => $this->humanizeAiError($err)];
        }

        try {
            $this->persistReport($exam, $data, $fp);
        } catch (\Throwable $e) {
            Log::error('Exam fairness persist failed', ['e' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Fairness analysis could not be completed. Please try again.'];
        }

        return ['ok' => true, 'message' => null, 'skipped' => false];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persistReport(Exam $exam, array $data, string $fingerprint): void
    {
        ExamFairnessReport::query()->updateOrCreate(
            ['exam_id' => $exam->id],
            [
                'fairness_score' => isset($data['fairness_score']) ? (int) $data['fairness_score'] : null,
                'overall_status' => isset($data['overall_status']) ? (string) $data['overall_status'] : null,
                'estimated_total_duration_minutes' => isset($data['estimated_total_duration_minutes'])
                    ? (int) $data['estimated_total_duration_minutes'] : null,
                'duration_status' => isset($data['duration_status']) ? (string) $data['duration_status'] : null,
                'difficulty_distribution' => $this->arrayOrNull($data['difficulty_distribution'] ?? null),
                'blooms_distribution' => $this->arrayOrNull($data['blooms_distribution'] ?? null),
                'cognitive_difficulty_map' => $this->arrayOrNull($data['cognitive_difficulty_map'] ?? null),
                'question_analysis' => $this->arrayOrNull($data['question_analysis'] ?? null),
                'recommendations' => $this->arrayOrNull($data['recommendations'] ?? null),
                'raw_ai_response' => $data,
                'questions_fingerprint' => $fingerprint,
                'analyzed_at' => now(),
            ],
        );
    }

    protected function humanizeAiError(string $raw): string
    {
        $r = strtolower($raw);
        if (str_contains($r, 'connection refused') || str_contains($r, 'could not resolve host')) {
            return 'AI fairness service is currently unavailable.';
        }

        if (str_contains($r, 'ai_service_url is not configured')) {
            return 'AI fairness service is not configured (missing AI_SERVICE_URL).';
        }

        if (str_contains($r, 'timed out') || str_contains($r, 'operation timed out')) {
            return 'The fairness analysis timed out. Try again with fewer questions.';
        }

        if (str_contains($r, 'openai') && str_contains($r, 'api key')) {
            return 'AI fairness service is not configured (missing OpenAI API key on the AI service).';
        }

        return 'Fairness analysis could not be completed. Please try again.';
    }

    /**
     * @param  list<string>  $topics
     * @return array<string, mixed>
     */
    protected function buildFastApiPayload(Exam $exam, array $topics): array
    {
        $questions = [];
        $n = 0;
        foreach ($exam->questions as $q) {
            $n++;
            $questions[] = [
                'question_id' => $q->id,
                'question_number' => $n,
                'type' => $q->q_type->value,
                'question' => (string) $q->question_text,
                'options' => $this->optionsForQuestion($q),
                'marks' => (int) $q->marks,
            ];
        }

        $courseLabel = trim(implode(' ', array_filter([
            (string) $exam->course_name,
            $exam->course_code ? '('.$exam->course_code.')' : null,
        ])));

        return [
            'exam_id' => $exam->id,
            'exam_title' => (string) $exam->title,
            'course' => $courseLabel !== '' ? $courseLabel : 'Course',
            'expected_duration_minutes' => max(0, (int) $exam->duration),
            'topics' => $topics,
            'questions' => $questions,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    protected function optionsForQuestion(Question $q): ?array
    {
        if ($q->q_type->value !== 'mcq') {
            return null;
        }
        $opts = array_values(array_filter([
            $q->option_a,
            $q->option_b,
            $q->option_c,
            $q->option_d,
            $q->option_e,
        ], fn ($v) => $v !== null && trim((string) $v) !== ''));

        return $opts === [] ? [] : $opts;
    }

    protected function arrayOrNull(mixed $v): ?array
    {
        return is_array($v) ? $v : null;
    }
}
