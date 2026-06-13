<?php

namespace App\Services;

use App\Models\AiGenerationRequest;
use App\Models\Exam;
use App\Models\OfficialQuestionReview;
use App\Models\Question;
use App\Models\QuestionAuditLog;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\QuestionUsageStat;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuestionBankService
{
    public function createDraftQuestion(User $lecturer, array $payload, ?AiGenerationRequest $request = null): QuestionBank
    {
        return DB::transaction(function () use ($lecturer, $payload, $request): QuestionBank {
            $question = QuestionBank::create([
                'course_id' => $payload['course_id'] ?? null,
                'lecturer_id' => $lecturer->id,
                'department_id' => $lecturer->department_id,
                'topic_id' => $payload['topic_id'] ?? null,
                'topic' => $payload['topic'] ?? null,
                'question_text' => $payload['question_text'],
                'question_type' => $payload['question_type'],
                'difficulty' => $payload['difficulty'] ?? 'medium',
                'correct_answer' => $payload['correct_answer'] ?? null,
                'model_answer' => $payload['model_answer'] ?? null,
                'marking_scheme' => $payload['marking_scheme'] ?? null,
                'suggested_marks' => $payload['suggested_marks'] ?? 1,
                'generated_by_ai' => (bool) ($payload['generated_by_ai'] ?? false),
                'ai_confidence' => $payload['ai_confidence'] ?? null,
                'source_reference' => $payload['source_reference'] ?? null,
                'status' => $payload['status'] ?? 'draft',
                'visibility' => $payload['visibility'] ?? 'personal',
                'created_by' => $lecturer->id,
                'ai_generation_request_id' => $request?->id,
                'submitted_for_admin_ai_review' => false,
            ]);

            $this->syncOptions($question, $payload['options'] ?? []);
            $this->storeVersion($question, $lecturer->id, 'created', 'Question created.');
            $this->audit('question_created', $question, $lecturer, [
                'generated_by_ai' => (bool) ($payload['generated_by_ai'] ?? false),
            ]);

            return $question->fresh(['options', 'usageStats']);
        });
    }

    public function updateQuestion(QuestionBank $question, User $actor, array $payload, string $reason = 'Updated by lecturer'): QuestionBank
    {
        return DB::transaction(function () use ($question, $actor, $payload, $reason): QuestionBank {
            $original = $question->replicate();

            if ($question->status === 'official') {
                $copy = $question->replicate();
                $copy->parent_question_id = $question->id;
                $copy->version = $question->version + 1;
                $copy->status = 'draft';
                $copy->visibility = 'personal';
                $copy->lecturer_id = $actor->id;
                $copy->created_by = $actor->id;
                $copy->approved_by = null;
                $copy->official_approved_by = null;
                $copy->rejection_reason = null;
                $copy->submitted_for_admin_ai_review = false;
                $copy->save();

                $this->syncOptions($copy, $payload['options'] ?? []);
                $this->storeVersion($copy, $actor->id, 'forked_from_official', $reason);
                $this->audit('official_question_duplicated', $copy, $actor, ['source_question_id' => $question->id]);

                return $copy->fresh(['options']);
            }

            $question->fill([
                'question_text' => $payload['question_text'] ?? $question->question_text,
                'question_type' => $payload['question_type'] ?? $question->question_type,
                'difficulty' => $payload['difficulty'] ?? $question->difficulty,
                'correct_answer' => $payload['correct_answer'] ?? $question->correct_answer,
                'model_answer' => $payload['model_answer'] ?? $question->model_answer,
                'marking_scheme' => $payload['marking_scheme'] ?? $question->marking_scheme,
                'suggested_marks' => $payload['suggested_marks'] ?? $question->suggested_marks,
                'topic' => $payload['topic'] ?? $question->topic,
                'course_id' => $payload['course_id'] ?? $question->course_id,
                'status' => $payload['status'] ?? $question->status,
            ]);
            $question->save();

            if (array_key_exists('options', $payload)) {
                $this->syncOptions($question, $payload['options'] ?? []);
            }

            $this->storeVersion($question, $actor->id, 'updated', $reason, $original->toArray());
            $this->audit('question_updated', $question, $actor, ['reason' => $reason]);

            return $question->fresh(['options']);
        });
    }

    public function approveQuestion(QuestionBank $question, User $actor): QuestionBank
    {
        $question->loadMissing('lecturer');

        if ($question->generated_by_ai && $this->aiBankReleaseRequiresHodApproval($question)) {
            if (! $this->actorMayReleaseAiBankAiQuestion($question, $actor)) {
                throw ValidationException::withMessages([
                    'approve' => 'AI-generated questions must be released by your head of department. Submit them for HoD review from the AI batch page.',
                ]);
            }
        }

        $question->update([
            'status' => 'approved',
            'visibility' => 'personal',
            'approved_by' => $actor->id,
            'rejection_reason' => null,
            'submitted_for_admin_ai_review' => false,
        ]);

        $this->audit('question_approved', $question, $actor);

        return $question->fresh();
    }

    public function rejectQuestion(QuestionBank $question, User $actor, ?string $reason = null): QuestionBank
    {
        $question->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => null,
            'submitted_for_admin_ai_review' => false,
        ]);

        $this->audit('question_rejected', $question, $actor, ['reason' => $reason]);

        return $question->fresh();
    }

    /**
     * Lecturer queues AI drafts for head-of-department release into the bank.
     *
     * @param  iterable<int|string, QuestionBank|int>  $questions
     */
    public function submitAiQuestionsForHodReview(User $lecturer, iterable $questions): int
    {
        if (! $lecturer->requiresHodAiQuestionReview()) {
            throw ValidationException::withMessages([
                'submit' => 'Head-of-department approval for AI questions is not enabled for your department. You can approve drafts yourself from the review page.',
            ]);
        }

        return (int) DB::transaction(function () use ($lecturer, $questions): int {
            $count = 0;
            foreach ($questions as $question) {
                if (! $question instanceof QuestionBank) {
                    $question = QuestionBank::query()->find($question);
                }
                if ($question === null) {
                    continue;
                }
                if ((int) $question->lecturer_id !== (int) $lecturer->id) {
                    abort(403);
                }
                if (! $question->generated_by_ai) {
                    continue;
                }
                if (! in_array($question->status, ['draft', 'rejected'], true)) {
                    continue;
                }

                $question->update([
                    'submitted_for_admin_ai_review' => true,
                    'status' => 'draft',
                    'rejection_reason' => null,
                ]);
                $this->audit('ai_question_submitted_for_hod_review', $question, $lecturer);
                $count++;
            }

            return $count;
        });
    }

    public function rejectAiQuestionInHodQueue(QuestionBank $question, User $hod, ?string $reason = null): QuestionBank
    {
        if (! $hod->isApprovedHod()) {
            abort(403);
        }

        $question->loadMissing('lecturer');
        if ((int) ($question->lecturer?->department_id ?? 0) !== (int) $hod->department_id) {
            abort(403, 'You can only review AI questions from lecturers in your department.');
        }

        if (! $question->generated_by_ai || ! $question->submitted_for_admin_ai_review || $question->status !== 'draft') {
            throw ValidationException::withMessages([
                'reject' => 'This question is not in the HoD AI review queue.',
            ]);
        }

        $question->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => null,
            'submitted_for_admin_ai_review' => false,
        ]);

        $this->audit('ai_question_rejected_by_hod', $question, $hod, ['reason' => $reason]);

        return $question->fresh();
    }

    public function archiveQuestion(QuestionBank $question, User $actor): QuestionBank
    {
        $question->update(['status' => 'archived']);
        $this->audit('question_archived', $question, $actor);

        return $question->fresh();
    }

    public function submitForOfficialReview(QuestionBank $question, User $actor): OfficialQuestionReview
    {
        return DB::transaction(function () use ($question, $actor): OfficialQuestionReview {
            $question->update([
                'status' => 'pending_official_approval',
                'visibility' => 'department',
            ]);

            $review = OfficialQuestionReview::create([
                'question_bank_id' => $question->id,
                'submitted_by' => $actor->id,
                'status' => 'pending',
            ]);

            $this->audit('question_submitted_for_official_review', $question, $actor, ['review_id' => $review->id]);

            return $review;
        });
    }

    public function reviewOfficialQuestion(OfficialQuestionReview $review, User $reviewer, bool $approved, ?string $reason = null): OfficialQuestionReview
    {
        return DB::transaction(function () use ($review, $reviewer, $approved, $reason): OfficialQuestionReview {
            $question = $review->question()->firstOrFail();

            $review->update([
                'reviewer_id' => $reviewer->id,
                'status' => $approved ? 'approved' : 'rejected',
                'review_note' => $approved ? 'Approved for official bank.' : null,
                'rejection_reason' => $approved ? null : $reason,
                'reviewed_at' => now(),
            ]);

            $question->update([
                'status' => $approved ? 'official' : 'rejected',
                'visibility' => $approved ? 'official' : 'personal',
                'official_approved_by' => $approved ? $reviewer->id : null,
                'rejection_reason' => $approved ? null : $reason,
            ]);

            $this->audit(
                $approved ? 'official_question_approved' : 'official_question_rejected',
                $question,
                $reviewer,
                ['review_id' => $review->id, 'reason' => $reason]
            );

            return $review->fresh(['question', 'reviewer']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{exam: Exam, selected_questions: Collection<int, QuestionBank>}
     */
    public function generateQuizFromBank(array $filters, User $lecturer): array
    {
        return DB::transaction(function () use ($filters, $lecturer): array {
            $courseLabel = trim((string) ($filters['course_label'] ?? 'Generated Course Quiz'));
            $title = trim((string) ($filters['title'] ?? ('Bank Quiz - '.now()->format('Y-m-d H:i'))));
            $questionCount = max(1, (int) ($filters['number_of_questions'] ?? 10));
            $difficultyMix = (array) ($filters['difficulty_mix'] ?? []);
            $typeMix = (array) ($filters['question_type_mix'] ?? []);
            $totalMarks = max($questionCount, (int) ($filters['total_marks'] ?? $questionCount));
            $duration = max(5, (int) ($filters['time_limit'] ?? 30));

            $query = QuestionBank::query()
                ->visibleToLecturer($lecturer)
                ->whereIn('status', ['approved', 'official'])
                ->whereNotIn('status', ['archived', 'draft', 'rejected', 'pending_official_approval']);

            if (! empty($filters['course_id'])) {
                $query->where('course_id', (int) $filters['course_id']);
            }
            if (! empty($filters['topics'])) {
                $query->whereIn('topic', array_filter((array) $filters['topics']));
            }
            if ($difficultyMix !== []) {
                $query->whereIn('difficulty', array_keys(array_filter($difficultyMix)));
            }
            if ($typeMix !== []) {
                $query->whereIn('question_type', array_keys(array_filter($typeMix)));
            }

            $pool = $query->with('options')->get()->shuffle()->unique('id')->values();
            $selected = $pool->take($questionCount)->values();
            if ($selected->count() === 0) {
                throw new \RuntimeException('No questions matched the selected filters.');
            }

            $exam = Exam::create([
                'user_id' => $lecturer->id,
                'title' => Str::limit($title, 100, ''),
                'course_name' => Str::limit($courseLabel, 100, ''),
                'course_code' => null,
                'instructions' => 'Generated from Question Bank. Review before publishing.',
                'duration' => $duration,
                'attempts_allowed' => 1,
                'start_datetime' => null,
                'end_datetime' => null,
                'is_published' => false,
                'results_released' => false,
                'exam_code' => strtoupper(Str::random(8)),
                'exam_type' => 'quiz',
                'shuffle_questions' => false,
                'submission_password_enabled' => false,
                'submission_password' => null,
            ]);

            $marksPerQuestion = max(1, (int) floor($totalMarks / $selected->count()));
            foreach ($selected as $item) {
                $marks = (int) ($item->suggested_marks ?: $marksPerQuestion);
                $this->copyQuestionBankItemToExam($exam, $item, $marks);
            }

            $this->audit('quiz_generated_from_bank', null, $lecturer, [
                'exam_id' => $exam->id,
                'question_count' => $selected->count(),
            ]);

            return [
                'exam' => $exam,
                'selected_questions' => $selected,
            ];
        });
    }

    /**
     * Attach approved (or official) bank questions the lecturer may use onto a practice quiz exam.
     *
     * @param  list<int|string>  $questionBankIds  Order is preserved; duplicates after the first are ignored.
     */
    public function attachBankQuestionsToPracticeExam(Exam $exam, User $lecturer, array $questionBankIds): int
    {
        if (! $exam->practice_mode) {
            throw new \InvalidArgumentException('Only practice-mode exams accept bank attachments.');
        }

        $r = $this->attachBankQuestionsToExam($exam, $lecturer, $questionBankIds, 'practice_quiz_bank_questions_attached');

        return $r['added'];
    }

    /**
     * Copy bank questions onto any exam (summative or practice). Skips items already linked by question_bank_id.
     *
     * @param  list<int|string>  $questionBankIds
     * @return array{added: int, skipped_duplicates: int, skipped_invalid: int}
     */
    public function attachBankQuestionsToExam(
        Exam $exam,
        User $lecturer,
        array $questionBankIds,
        ?string $auditEvent = 'exam_bank_questions_attached',
    ): array {
        return DB::transaction(function () use ($exam, $lecturer, $questionBankIds, $auditEvent): array {
            $ids = collect($questionBankIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($ids === []) {
                return ['added' => 0, 'skipped_duplicates' => 0, 'skipped_invalid' => 0];
            }

            $existing = $exam->questions()
                ->whereNotNull('question_bank_id')
                ->pluck('question_bank_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $items = QuestionBank::query()
                ->visibleToLecturer($lecturer)
                ->whereIn('status', ['approved', 'official'])
                ->whereNotIn('status', ['archived', 'draft', 'rejected', 'pending_official_approval'])
                ->whereIn('id', $ids)
                ->with('options')
                ->get()
                ->keyBy('id');

            $added = 0;
            $skippedDuplicates = 0;
            $skippedInvalid = 0;
            foreach ($ids as $id) {
                if (in_array($id, $existing, true)) {
                    $skippedDuplicates++;

                    continue;
                }
                $item = $items->get($id);
                if ($item === null) {
                    $skippedInvalid++;

                    continue;
                }
                $marks = max(1, (int) ($item->suggested_marks ?: 1));
                $this->copyQuestionBankItemToExam($exam, $item, $marks);
                $existing[] = $id;
                $added++;
            }

            if ($added > 0 && $auditEvent !== null) {
                $this->audit($auditEvent, null, $lecturer, [
                    'exam_id' => $exam->id,
                    'added' => $added,
                    'practice_mode' => (bool) $exam->practice_mode,
                ]);
            }

            return [
                'added' => $added,
                'skipped_duplicates' => $skippedDuplicates,
                'skipped_invalid' => $skippedInvalid,
            ];
        });
    }

    private function copyQuestionBankItemToExam(Exam $exam, QuestionBank $item, int $marks): Question
    {
        $item->loadMissing('options');

        $question = Question::create([
            'exam_id' => $exam->id,
            'question_bank_id' => $item->id,
            'question_text' => $item->question_text,
            'q_type' => $this->toExamQuestionType($item->question_type),
            'correct_option' => $item->question_type === 'mcq'
                ? optional($item->options->firstWhere('is_correct', true))->option_key
                : ($item->correct_answer ?? $item->model_answer),
            'marks' => max(1, $marks),
        ]);

        if ($item->question_type === 'mcq') {
            $optionMap = $item->options->keyBy(fn (QuestionOption $o) => strtolower((string) $o->option_key));
            $question->update([
                'option_a' => optional($optionMap->get('a'))->option_text,
                'option_b' => optional($optionMap->get('b'))->option_text,
                'option_c' => optional($optionMap->get('c'))->option_text,
                'option_d' => optional($optionMap->get('d'))->option_text,
                'option_e' => optional($optionMap->get('e'))->option_text,
            ]);
        }

        $usageStat = QuestionUsageStat::firstOrNew(['question_bank_id' => $item->id]);
        $usageStat->times_used = (int) ($usageStat->times_used ?? 0) + 1;
        $usageStat->last_used_at = now();
        $usageStat->save();

        return $question;
    }

    public function refreshQuestionUsageStats(int $questionBankId): void
    {
        $stats = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('questions.question_bank_id', $questionBankId)
            ->selectRaw(
                'COUNT(*) as usage_count, AVG(COALESCE(answers.marks_awarded, CASE WHEN answers.is_correct THEN questions.marks ELSE 0 END, 0)) as avg_score'
            )
            ->first();

        $wrongAnswers = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('questions.question_bank_id', $questionBankId)
            ->where(function ($q): void {
                $q->where('answers.is_correct', false)
                    ->orWhereNull('answers.is_correct');
            })
            ->selectRaw('COALESCE(NULLIF(answers.selected_option, \'\'), NULLIF(answers.theory_answer, \'\'), \'No answer\') as wrong_answer, COUNT(*) as total')
            ->groupBy('wrong_answer')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => ['answer' => $row->wrong_answer, 'count' => (int) $row->total])
            ->all();

        $aggregate = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('questions.question_bank_id', $questionBankId)
            ->selectRaw('AVG(CASE WHEN answers.is_correct THEN 1.0 ELSE 0.0 END) as correct_rate')
            ->first();

        $correctPercentage = round((float) (($aggregate->correct_rate ?? 0) * 100), 2);
        $averageScore = round((float) ($stats->avg_score ?? 0), 2);
        $difficultyPerformance = round(100 - $correctPercentage, 2);

        QuestionUsageStat::updateOrCreate(
            ['question_bank_id' => $questionBankId],
            [
                'times_used' => (int) ($stats->usage_count ?? 0),
                'average_score' => $averageScore,
                'correct_percentage' => $correctPercentage,
                'difficulty_performance' => $difficultyPerformance,
                'common_wrong_answers' => $wrongAnswers,
                'ai_grading_consistency' => null,
                'last_used_at' => now(),
            ]
        );
    }

    public function audit(string $eventType, ?QuestionBank $question, ?User $actor, array $metadata = [], ?Request $request = null): void
    {
        QuestionAuditLog::create([
            'question_bank_id' => $question?->id,
            'actor_id' => $actor?->id,
            'event_type' => $eventType,
            'description' => str_replace('_', ' ', $eventType),
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(QuestionBank $question, array $options): void
    {
        $question->options()->delete();
        foreach ($options as $option) {
            if (! isset($option['key'], $option['text'])) {
                continue;
            }

            $text = trim((string) $option['text']);
            if ($text === '') {
                continue;
            }

            QuestionOption::create([
                'question_bank_id' => $question->id,
                'option_key' => strtoupper(substr((string) $option['key'], 0, 1)),
                'option_text' => $text,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function storeVersion(
        QuestionBank $question,
        int $actorId,
        string $changeType,
        ?string $reason = null,
        ?array $before = null
    ): void {
        $snapshot = $question->load('options')->toArray();
        if ($before !== null) {
            $snapshot = [
                'before' => $before,
                'after' => $snapshot,
            ];
        }

        QuestionVersion::create([
            'question_bank_id' => $question->id,
            'version' => (int) ($question->version ?? 1),
            'changed_by' => $actorId,
            'change_type' => $changeType,
            'change_reason' => $reason,
            'snapshot' => $snapshot,
        ]);
    }

    private function toExamQuestionType(string $bankType): string
    {
        return match ($bankType) {
            'short_answer' => 'theory',
            default => $bankType,
        };
    }

    private function aiBankReleaseRequiresHodApproval(QuestionBank $question): bool
    {
        $question->loadMissing('lecturer.department');

        return (bool) ($question->lecturer?->department?->require_hod_approval_for_ai_questions ?? false);
    }

    private function actorMayReleaseAiBankAiQuestion(QuestionBank $question, User $actor): bool
    {
        if (! $this->aiBankReleaseRequiresHodApproval($question)) {
            return true;
        }

        if (! $actor->isApprovedHod()) {
            return false;
        }

        $question->loadMissing('lecturer');

        return (int) $actor->department_id === (int) ($question->lecturer?->department_id ?? 0);
    }
}
