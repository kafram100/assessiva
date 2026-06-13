<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Attempt;
use App\Models\ExamAllowedIndex;
use App\Models\LearningTwin;
use App\Models\PracticeQuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningTwinService
{
    public function __construct(private readonly AiService $ai) {}

    public function contextKey(?int $courseId): string
    {
        return $courseId !== null ? 'course:'.$courseId : 'all';
    }

    /**
     * @return array{student: array<string, mixed>, academic_data: array<string, mixed>, topic_performance: list<array<string, mixed>>, recent_attempts: list<array<string, mixed>>}
     */
    public function buildPayload(User $lecturer, string $studentIndex, ?int $courseId): array
    {
        $idx = ExamAllowedIndex::normalize($studentIndex);
        if ($idx === '') {
            throw new \InvalidArgumentException('Invalid student index.');
        }

        $attempts = $this->attemptsQuery($lecturer, $idx, $courseId)
            ->orderByDesc('submit_time')
            ->get();

        if ($attempts->isEmpty()) {
            throw new \RuntimeException('No completed attempts found for this student in your roster scope.');
        }

        $latest = $attempts->first();
        $examCount = $attempts->count();
        $percents = [];
        foreach ($attempts as $a) {
            $tm = (int) $a->total_marks;
            if ($tm > 0) {
                $percents[] = ((float) $a->score / $tm) * 100;
            }
        }
        $avgPercent = $percents !== [] ? round(array_sum($percents) / count($percents)) : 0;

        $topicScores = [];
        foreach ($attempts as $attempt) {
            foreach ($attempt->answers as $answer) {
                $q = $answer->question;
                if (! $q) {
                    continue;
                }
                $topic = $q->questionBank?->topic;
                $label = ($topic !== null && trim((string) $topic) !== '')
                    ? trim((string) $topic)
                    : ($q->q_type instanceof QuestionType
                        ? ucfirst(str_replace('_', ' ', $q->q_type->value))
                        : 'General');
                $marks = max(1, (int) $q->marks);
                $awarded = $answer->marks_awarded;
                if ($awarded === null && $q->q_type === QuestionType::Mcq) {
                    $sel = strtoupper(trim((string) ($answer->selected_option ?? '')));
                    $cor = strtoupper(trim((string) ($q->correct_option ?? '')));
                    $awarded = ($sel !== '' && $cor !== '' && hash_equals($cor, $sel)) ? $marks : 0;
                }
                $awarded = (int) ($awarded ?? 0);
                $pct = min(100, max(0, ($awarded / $marks) * 100));
                if (! isset($topicScores[$label])) {
                    $topicScores[$label] = ['sum' => 0.0, 'n' => 0];
                }
                $topicScores[$label]['sum'] += $pct;
                $topicScores[$label]['n']++;
            }
        }

        $topicPerformance = [];
        foreach ($topicScores as $topic => $agg) {
            $topicPerformance[] = [
                'topic' => $topic,
                'average_percent' => $agg['n'] > 0 ? round($agg['sum'] / $agg['n']) : 0,
                'items' => $agg['n'],
            ];
        }
        usort($topicPerformance, fn ($a, $b) => $a['average_percent'] <=> $b['average_percent']);

        $recentAttempts = [];
        foreach ($attempts->take(12) as $a) {
            $ex = $a->exam;
            $tm = (int) $a->total_marks;
            $recentAttempts[] = [
                'exam_title' => $ex?->title ?? 'Exam',
                'course' => $ex?->course_name ?? '',
                'score' => (float) $a->score,
                'total_marks' => $tm,
                'percent' => $tm > 0 ? round(((float) $a->score / $tm) * 100) : 0,
                'submitted_at' => $a->submit_time?->toIso8601String(),
                'cheat_events' => (int) ($a->cheat_events_count ?? 0),
            ];
        }

        $engagement = $this->engagementIndex($attempts);
        $failures = $attempts->filter(function (Attempt $a) {
            $tm = (int) $a->total_marks;

            return $tm > 0 && ((float) $a->score / $tm) < 0.5;
        })->count();

        return [
            'student' => [
                'student_index' => $idx,
                'display_name' => $latest->student_fullname,
                'email' => $latest->student_email,
            ],
            'academic_data' => [
                'completed_attempts' => $examCount,
                'average_percent' => $avgPercent,
                'below_fifty_percent_count' => $failures,
                'engagement_index' => $engagement,
                'lecturer_id' => $lecturer->id,
                'course_scope' => $courseId,
                'practice_quiz_activity' => $this->practiceQuizSummary($lecturer, $idx),
            ],
            'topic_performance' => $topicPerformance,
            'recent_attempts' => $recentAttempts,
        ];
    }

    public function generateAndStore(User $lecturer, string $studentIndex, ?int $courseId, bool $force = false): LearningTwin
    {
        $idx = ExamAllowedIndex::normalize($studentIndex);
        $ck = $this->contextKey($courseId);

        $existing = LearningTwin::query()
            ->where('lecturer_user_id', $lecturer->id)
            ->where('student_index', $idx)
            ->where('context_key', $ck)
            ->first();

        if ($existing !== null && ! $force) {
            return $existing;
        }

        $payload = $this->buildPayload($lecturer, $idx, $courseId);

        try {
            $data = $this->ai->postJson('generate-learning-twin', $payload);
        } catch (\Throwable $e) {
            Log::error('Learning twin AI error', ['message' => $e->getMessage()]);
            throw $e;
        }

        if (! ($data['success'] ?? false)) {
            throw new \RuntimeException((string) ($data['message'] ?? 'AI service returned failure.'));
        }

        $attrs = [
            'lecturer_user_id' => $lecturer->id,
            'student_index' => $idx,
            'course_id' => $courseId,
            'context_key' => $ck,
            'source_attempt_id' => null,
            'input_snapshot' => $payload,
            'academic_health_score' => isset($data['academic_health_score']) ? (int) $data['academic_health_score'] : null,
            'risk_level' => isset($data['risk_level']) ? (string) $data['risk_level'] : null,
            'predicted_next_score' => isset($data['predicted_next_score']) ? (int) $data['predicted_next_score'] : null,
            'strengths' => is_array($data['strengths'] ?? null) ? $data['strengths'] : [],
            'weaknesses' => is_array($data['weaknesses'] ?? null) ? $data['weaknesses'] : [],
            'learning_pattern' => isset($data['learning_pattern']) ? (string) $data['learning_pattern'] : null,
            'recommended_actions' => is_array($data['recommended_actions'] ?? null) ? $data['recommended_actions'] : [],
            'revision_focus' => is_array($data['revision_focus'] ?? null) ? $data['revision_focus'] : [],
            'lecturer_intervention' => isset($data['lecturer_intervention']) ? (string) $data['lecturer_intervention'] : null,
            'raw_ai_response' => $data,
            'generated_at' => now(),
        ];

        return DB::transaction(function () use ($existing, $attrs): LearningTwin {
            if ($existing !== null) {
                $existing->update($attrs);

                return $existing->fresh();
            }

            return LearningTwin::query()->create($attrs);
        });
    }

    public function findForAttempt(Attempt $attempt): ?LearningTwin
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            return null;
        }
        $lecturerId = (int) $exam->user_id;
        $idx = ExamAllowedIndex::normalize((string) $attempt->student_index);
        $courseId = $exam->course_id !== null ? (int) $exam->course_id : null;
        $ck = $this->contextKey($courseId);

        return LearningTwin::query()
            ->where('lecturer_user_id', $lecturerId)
            ->where('student_index', $idx)
            ->where('context_key', $ck)
            ->first();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Attempt>  $attempts
     */
    private function engagementIndex($attempts): int
    {
        if ($attempts->isEmpty()) {
            return 50;
        }
        $scores = [];
        foreach ($attempts as $a) {
            $flags = (int) ($a->cheat_events_count ?? 0);
            $scores[] = max(0, 100 - min(100, $flags * 20));
        }

        return (int) round(array_sum($scores) / count($scores));
    }

    private function attemptsQuery(User $lecturer, string $normalizedIndex, ?int $courseId)
    {
        return Attempt::query()
            ->whereHas('exam', function ($q) use ($lecturer, $courseId): void {
                $q->where('user_id', $lecturer->id);
                if ($courseId !== null) {
                    $q->where('course_id', $courseId);
                }
            })
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalizedIndex])
            ->where('status', 'completed')
            ->withCount('cheatEvents')
            ->with(['exam', 'answers.question.questionBank']);
    }

    /**
     * @return array{sessions: int, average_percent: ?int, last_submitted_at: ?string, top_weak_topics_practice: list<string>}
     */
    private function practiceQuizSummary(User $lecturer, string $normalizedIndex): array
    {
        $rows = PracticeQuizAttempt::query()
            ->whereHas('quiz', function ($q) use ($lecturer): void {
                $q->where('user_id', $lecturer->id)->where('practice_mode', true);
            })
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalizedIndex])
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get(['score', 'total_marks', 'submitted_at', 'weak_topics']);

        if ($rows->isEmpty()) {
            return [
                'sessions' => 0,
                'average_percent' => null,
                'last_submitted_at' => null,
                'top_weak_topics_practice' => [],
            ];
        }

        $pcts = [];
        $topicCounts = [];
        foreach ($rows as $r) {
            $tm = max(1, (int) $r->total_marks);
            $pcts[] = ((float) $r->score / $tm) * 100;
            $wt = $r->weak_topics;
            if (! is_array($wt)) {
                continue;
            }
            foreach ($wt as $t) {
                $t = trim((string) $t);
                if ($t === '') {
                    continue;
                }
                $topicCounts[$t] = ($topicCounts[$t] ?? 0) + 1;
            }
        }
        arsort($topicCounts);

        return [
            'sessions' => $rows->count(),
            'average_percent' => $pcts !== [] ? (int) round(array_sum($pcts) / count($pcts)) : null,
            'last_submitted_at' => $rows->first()->submitted_at?->toIso8601String(),
            'top_weak_topics_practice' => array_keys(array_slice($topicCounts, 0, 8, true)),
        ];
    }
}
