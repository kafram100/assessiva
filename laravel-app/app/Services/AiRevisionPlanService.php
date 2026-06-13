<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\Question;
use App\Models\RevisionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiRevisionPlanService
{
    public function __construct(private readonly AiService $aiService) {}

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Attempt $attempt): array
    {
        $attempt->loadMissing(['exam.course', 'answers.question.questionBank']);

        $exam = $attempt->exam;
        if ($exam === null) {
            throw new \InvalidArgumentException('Attempt has no exam.');
        }

        $failed = [];
        $weakTopicSet = [];

        foreach ($attempt->answers as $answer) {
            $q = $answer->question;
            if (! $q instanceof Question) {
                continue;
            }

            $marks = (int) $q->marks;
            $awarded = $answer->marks_awarded;
            if ($awarded === null) {
                $awarded = $this->inferAwardedMarks($answer, $q);
            }

            if ($marks <= 0) {
                continue;
            }

            if ($awarded >= $marks) {
                continue;
            }

            $topic = $q->questionBank?->topic;
            if ($topic !== null && trim((string) $topic) !== '') {
                $weakTopicSet[trim((string) $topic)] = true;
            } else {
                $typeLabel = $q->q_type instanceof QuestionType
                    ? ucfirst(str_replace('_', ' ', $q->q_type->value))
                    : 'General';
                $weakTopicSet[$typeLabel] = true;
            }

            $failed[] = [
                'question' => $this->truncate((string) $q->question_text, 2000),
                'student_answer' => $this->formatStudentAnswer($answer, $q),
                'correct_answer' => $this->formatCorrectAnswer($q),
                'topic' => $topic !== null && trim((string) $topic) !== ''
                    ? trim((string) $topic)
                    : ($q->q_type instanceof QuestionType
                        ? ucfirst(str_replace('_', ' ', $q->q_type->value))
                        : 'General'),
                'marks' => $marks,
                'score_awarded' => (int) $awarded,
            ];
        }

        $weakTopics = array_keys($weakTopicSet);
        if ($weakTopics === [] && $failed !== []) {
            $weakTopics = array_values(array_unique(array_column($failed, 'topic')));
        }

        $pct = ((int) $attempt->total_marks) > 0
            ? round(((float) $attempt->score / max(1, (int) $attempt->total_marks)) * 100)
            : 0;

        $performanceHistory = $this->performanceHistoryForStudent($attempt);

        return [
            'student_id' => $attempt->id,
            'student_name' => (string) ($attempt->student_fullname ?? 'Student'),
            'course' => (string) ($exam->course_name ?: $exam->title),
            'exam_title' => (string) $exam->title,
            'overall_score' => round((float) $attempt->score, 2),
            'overall_percent' => $pct,
            'weak_topics' => $weakTopics,
            'failed_questions' => $failed,
            'performance_history' => $performanceHistory,
        ];
    }

    /**
     * @return list<array{assessment: string, score: float|int}>
     */
    private function performanceHistoryForStudent(Attempt $attempt): array
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            return [];
        }

        $ownerId = (int) $exam->user_id;
        $index = ExamAllowedIndex::normalize((string) $attempt->student_index);

        $rows = Attempt::query()
            ->with('exam')
            ->where('id', '!=', $attempt->id)
            ->where('status', 'completed')
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$index])
            ->whereHas('exam', fn ($q) => $q->where('user_id', $ownerId))
            ->orderByDesc('submit_time')
            ->limit(6)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $ex = $row->exam;
            $tm = (int) ($row->total_marks ?? 0);
            $sc = (float) ($row->score ?? 0);
            $out[] = [
                'assessment' => $ex?->title ?? 'Assessment',
                'score' => $tm > 0 ? round(($sc / $tm) * 100) : (int) round($sc),
            ];
        }

        return $out;
    }

    public function generateAndStore(Attempt $attempt, ?User $_actor = null, bool $force = false): RevisionPlan
    {
        $exam = $attempt->exam()->first();
        if ($exam === null) {
            throw new \InvalidArgumentException('Invalid attempt.');
        }

        if (! $exam->results_released || $attempt->status !== 'completed') {
            throw new \RuntimeException('Results must be released and the attempt completed before generating a revision plan.');
        }

        $existing = RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->first();
        if ($existing !== null && ! $force) {
            return $existing;
        }

        $payload = $this->buildPayload($attempt);

        try {
            $data = $this->aiService->postJson('generate-revision-plan', $payload);
        } catch (\Throwable $e) {
            Log::error('Revision plan FastAPI error', [
                'attempt_id' => $attempt->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        if (! ($data['success'] ?? false)) {
            throw new \RuntimeException((string) ($data['message'] ?? 'AI service returned failure.'));
        }

        return DB::transaction(function () use ($attempt, $exam, $data, $payload, $existing): RevisionPlan {
            $attrs = [
                'exam_id' => $exam->id,
                'exam_attempt_id' => $attempt->id,
                'student_index' => $attempt->student_index,
                'student_name' => $attempt->student_fullname,
                'student_email' => $attempt->student_email,
                'course_name' => $exam->course_name ?: $exam->title,
                'overall_score' => $attempt->score,
                'risk_level' => isset($data['risk_level']) ? (string) $data['risk_level'] : null,
                'weak_topics' => is_array($data['weak_topics'] ?? null) ? $data['weak_topics'] : $payload['weak_topics'],
                'failed_questions' => $payload['failed_questions'],
                'summary' => isset($data['summary']) ? (string) $data['summary'] : null,
                'revision_notes' => is_array($data['revision_notes'] ?? null) ? $data['revision_notes'] : [],
                'flashcards' => is_array($data['flashcards'] ?? null) ? $data['flashcards'] : [],
                'practice_questions' => is_array($data['practice_questions'] ?? null) ? $data['practice_questions'] : [],
                'study_plan' => is_array($data['study_plan'] ?? null) ? $data['study_plan'] : [],
                'recommended_actions' => is_array($data['recommended_actions'] ?? null) ? $data['recommended_actions'] : [],
                'ai_provider' => (string) ($data['provider'] ?? $data['model'] ?? 'fastapi'),
                'raw_ai_response' => $data,
                'generated_at' => now(),
            ];

            if ($existing !== null) {
                $existing->update($attrs);

                return $existing->fresh();
            }

            return RevisionPlan::query()->create($attrs);
        });
    }

    /**
     * Best-effort generation for every completed attempt (skips failures and existing rows).
     */
    public function generateAllForReleasedExam(Exam $exam): void
    {
        if (! $exam->results_released) {
            return;
        }

        $attempts = Attempt::query()
            ->where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->get();

        foreach ($attempts as $attempt) {
            if (RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->exists()) {
                continue;
            }
            try {
                $this->generateAndStore($attempt, null, false);
            } catch (\Throwable $e) {
                Log::warning('Skipped revision plan for attempt', [
                    'attempt_id' => $attempt->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function inferAwardedMarks(Answer $answer, Question $q): int
    {
        if ($q->q_type === QuestionType::Mcq) {
            $sel = strtoupper(trim((string) ($answer->selected_option ?? '')));
            $cor = strtoupper(trim((string) ($q->correct_option ?? '')));

            return ($sel !== '' && $cor !== '' && hash_equals($cor, $sel)) ? (int) $q->marks : 0;
        }

        return (int) ($answer->marks_awarded ?? 0);
    }

    private function formatStudentAnswer(Answer $answer, Question $q): string
    {
        if ($q->q_type === QuestionType::Mcq) {
            $key = strtoupper(trim((string) ($answer->selected_option ?? '')));
            if ($key === '') {
                return '(no answer)';
            }
            $field = 'option_'.strtolower($key);
            $text = trim((string) ($q->{$field} ?? ''));

            return $text !== '' ? "{$key}. {$text}" : $key;
        }

        $t = trim((string) ($answer->theory_answer ?? ''));
        if ($t === '') {
            return '(no answer)';
        }

        return $this->truncate($t, 1500);
    }

    private function formatCorrectAnswer(Question $q): string
    {
        if ($q->q_type === QuestionType::Mcq) {
            $key = strtoupper(trim((string) ($q->correct_option ?? '')));
            if ($key === '') {
                return '—';
            }
            $field = 'option_'.strtolower($key);
            $text = trim((string) ($q->{$field} ?? ''));

            return $text !== '' ? "{$key}. {$text}" : $key;
        }

        return $this->truncate(trim((string) ($q->correct_option ?? '')), 1500);
    }

    private function truncate(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }

        return substr($s, 0, $max).'…';
    }
}
