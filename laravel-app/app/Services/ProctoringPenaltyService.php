<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\Exam;
use App\Models\ProctoringPenaltyRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProctoringPenaltyService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_DISMISSED = 'dismissed';

    /** @return list<string> */
    public static function supportedEventTypes(): array
    {
        $fromMap = array_keys(CheatEvent::$severityMap);
        $extra = [
            'copy_paste',
            'face_not_visible',
            'audio_noise',
            'microphone_disabled',
            'suspicious_movement',
            'network_disconnect',
            'fullscreen_exit',
        ];

        return array_values(array_unique(array_merge($fromMap, $extra)));
    }

    public function penaltyMarksMagnitude(float|string|null $value): float
    {
        return max(0, abs((float) $value));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<string, ProctoringPenaltyRule>
     */
    public function getRulesForExam(Exam $exam): Collection
    {
        return ProctoringPenaltyRule::query()->where('exam_id', $exam->id)->get()->keyBy('event_type');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rulesPayloadForExam(Exam $exam): array
    {
        $stored = $this->getRulesForExam($exam);
        $out = [];
        foreach (self::supportedEventTypes() as $type) {
            $row = $stored->get($type);
            $out[] = [
                'id' => $row?->id,
                'event_type' => $type,
                'penalty_marks' => $row ? (float) $row->penalty_marks : 0.0,
                'is_enabled' => $row ? (bool) $row->is_enabled : false,
                'severity' => $row?->severity,
                'description' => $row?->description,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    public function upsertRules(Exam $exam, array $rules, ?int $userId): void
    {
        DB::transaction(function () use ($exam, $rules, $userId): void {
            foreach ($rules as $r) {
                if (! is_array($r) || empty($r['event_type'])) {
                    continue;
                }
                $type = (string) $r['event_type'];
                if (! in_array($type, self::supportedEventTypes(), true)) {
                    continue;
                }
                ProctoringPenaltyRule::query()->updateOrCreate(
                    ['exam_id' => $exam->id, 'event_type' => $type],
                    [
                        'penalty_marks' => $this->penaltyMarksMagnitude($r['penalty_marks'] ?? 0),
                        'is_enabled' => (bool) ($r['is_enabled'] ?? false),
                        'severity' => isset($r['severity']) && $r['severity'] !== '' ? (string) $r['severity'] : null,
                        'description' => isset($r['description']) && $r['description'] !== '' ? (string) $r['description'] : null,
                        'created_by' => $userId,
                    ]
                );
            }
            $this->recalculateAllAttemptsForExam($exam);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRule(ProctoringPenaltyRule $rule, array $data, ?int $userId): void
    {
        DB::transaction(function () use ($rule, $data, $userId): void {
            $rule->fill([
                'penalty_marks' => array_key_exists('penalty_marks', $data)
                    ? $this->penaltyMarksMagnitude($data['penalty_marks'])
                    : $rule->penalty_marks,
                'is_enabled' => array_key_exists('is_enabled', $data) ? (bool) $data['is_enabled'] : $rule->is_enabled,
                'severity' => array_key_exists('severity', $data)
                    ? ($data['severity'] !== null && $data['severity'] !== '' ? (string) $data['severity'] : null)
                    : $rule->severity,
                'description' => array_key_exists('description', $data)
                    ? ($data['description'] !== null && $data['description'] !== '' ? (string) $data['description'] : null)
                    : $rule->description,
                'created_by' => $userId ?? $rule->created_by,
            ]);
            $rule->save();
            $exam = $rule->exam;
            if ($exam) {
                $this->recalculateAllAttemptsForExam($exam);
            }
        });
    }

    public function sumAppliedPenalties(Attempt $attempt): float
    {
        $total = CheatEvent::query()
            ->where('attempt_id', $attempt->id)
            ->where('review_status', self::STATUS_APPLIED)
            ->where('penalty_applied', true)
            ->get()
            ->sum(fn (CheatEvent $e) => $this->penaltyMarksMagnitude($e->penalty_marks));

        return (float) $total;
    }

    /**
     * @return array{raw_score: float, total_penalty: float, adjusted_score: float, events: list<array<string, mixed>>}
     */
    public function penaltySummary(Attempt $attempt): array
    {
        $attempt->refresh();
        $raw = (float) ($attempt->raw_score ?? $attempt->score ?? 0);
        $total = (float) ($attempt->proctoring_penalty_total ?? 0);
        $adjusted = (float) ($attempt->score ?? max(0, $raw - $total));

        $events = CheatEvent::query()
            ->where('attempt_id', $attempt->id)
            ->orderByDesc('created_at')
            ->get(['id', 'event_type', 'severity', 'created_at', 'penalty_marks', 'penalty_applied', 'review_status', 'reviewed_at'])
            ->map(fn (CheatEvent $e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'severity' => $e->severity,
                'created_at' => $e->created_at?->toIso8601String(),
                'penalty_marks' => $e->penalty_marks !== null ? (float) $e->penalty_marks : null,
                'penalty_applied' => (bool) $e->penalty_applied,
                'review_status' => $e->review_status ?? self::STATUS_PENDING,
                'reviewed_at' => $e->reviewed_at?->toIso8601String(),
            ])
            ->all();

        return [
            'raw_score' => $raw,
            'total_penalty' => $total,
            'adjusted_score' => $adjusted,
            'events' => $events,
        ];
    }

    public function recalculateAdjustedScore(Attempt $attempt): void
    {
        $attempt->refresh();
        $raw = $attempt->raw_score;
        if ($raw === null) {
            $raw = (float) ($attempt->score ?? 0);
            $attempt->raw_score = $raw;
        } else {
            $raw = (float) $raw;
        }

        $penalty = $this->sumAppliedPenalties($attempt);
        $adjusted = max(0, $raw - $penalty);

        $attempt->proctoring_penalty_total = $penalty;
        $attempt->score = $adjusted;
        $attempt->save();
    }

    public function recalculateAllAttemptsForExam(Exam $exam): void
    {
        Attempt::query()
            ->where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->orderBy('id')
            ->chunkById(100, function ($attempts): void {
                foreach ($attempts as $attempt) {
                    $this->recalculateAdjustedScore($attempt);
                }
            });
    }

    public function setRawScoreFromGrading(Attempt $attempt, float $questionBasedTotal): void
    {
        $attempt->raw_score = max(0, $questionBasedTotal);
        $attempt->save();
        $this->recalculateAdjustedScore($attempt);
    }

    public function ensureRuleForEvent(Exam $exam, string $eventType): ?ProctoringPenaltyRule
    {
        return ProctoringPenaltyRule::query()
            ->where('exam_id', $exam->id)
            ->where('event_type', $eventType)
            ->first();
    }

    public function applyPenaltyToEvent(CheatEvent $event, int $reviewerId): void
    {
        $attempt = $event->attempt;
        if (! $attempt) {
            abort(404);
        }
        $exam = $attempt->exam;
        if (! $exam) {
            abort(404);
        }
        $rule = $this->ensureRuleForEvent($exam, (string) $event->event_type);
        $marks = 0.0;
        if ($rule && $rule->is_enabled) {
            $marks = $this->penaltyMarksMagnitude($rule->penalty_marks);
        }

        DB::transaction(function () use ($event, $reviewerId, $marks, $attempt): void {
            $event->update([
                'penalty_marks' => $marks,
                'penalty_applied' => true,
                'review_status' => self::STATUS_APPLIED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
            $this->recalculateAdjustedScore($attempt);
        });
    }

    public function ignoreEvent(CheatEvent $event, int $reviewerId, string $status = self::STATUS_IGNORED): void
    {
        if (! in_array($status, [self::STATUS_IGNORED, self::STATUS_DISMISSED, self::STATUS_PENDING], true)) {
            $status = self::STATUS_IGNORED;
        }

        DB::transaction(function () use ($event, $reviewerId, $status): void {
            $attempt = $event->attempt;
            $event->update([
                'penalty_marks' => null,
                'penalty_applied' => false,
                'review_status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
            if ($attempt) {
                $this->recalculateAdjustedScore($attempt);
            }
        });
    }

    public function markReviewedNoPenalty(CheatEvent $event, int $reviewerId): void
    {
        $this->ignoreEvent($event, $reviewerId, self::STATUS_DISMISSED);
    }

    /**
     * @param  list<int>  $eventIds
     */
    public function applyPenaltyToEvents(Exam $exam, Attempt $attempt, array $eventIds, int $reviewerId): int
    {
        $count = 0;
        foreach ($eventIds as $id) {
            $ev = CheatEvent::query()->where('id', (int) $id)->where('attempt_id', $attempt->id)->first();
            if (! $ev || (int) $attempt->exam_id !== (int) $exam->id) {
                continue;
            }
            $this->applyPenaltyToEvent($ev, $reviewerId);
            $count++;
        }

        return $count;
    }
}
