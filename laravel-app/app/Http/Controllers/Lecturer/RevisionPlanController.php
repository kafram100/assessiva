<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\RevisionPlan;
use App\Services\AiRevisionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RevisionPlanController extends Controller
{
    public function __construct(
        private readonly AiRevisionPlanService $revisionPlanService
    ) {}

    public function generateForAttempt(Request $request, Exam $exam, Attempt $attempt): RedirectResponse
    {
        Gate::authorize('view', $exam);
        $this->assertAttemptBelongsToExam($exam, $attempt);

        try {
            $this->revisionPlanService->generateAndStore($attempt, $request->user(), false);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not generate revision plan: '.$e->getMessage());
        }

        return back()->with('success', 'AI revision plan generated for this student.');
    }

    public function regenerateForAttempt(Request $request, Exam $exam, Attempt $attempt): RedirectResponse
    {
        Gate::authorize('view', $exam);
        $this->assertAttemptBelongsToExam($exam, $attempt);

        try {
            $this->revisionPlanService->generateAndStore($attempt, $request->user(), true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not regenerate revision plan: '.$e->getMessage());
        }

        return back()->with('success', 'Revision plan regenerated.');
    }

    public function generateForExam(Request $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('view', $exam);

        if (! $exam->results_released) {
            return back()->with('error', 'Release results before bulk-generating revision plans.');
        }

        $this->revisionPlanService->generateAllForReleasedExam($exam);

        return back()->with('success', 'Revision plan generation ran for all completed attempts (skipped where already present or on AI errors).');
    }

    public function viewForAttempt(Request $request, Exam $exam, Attempt $attempt): Response
    {
        Gate::authorize('view', $exam);
        $this->assertAttemptBelongsToExam($exam, $attempt);

        $plan = RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->first();

        return Inertia::render('Lecturer/RevisionPlans/Show', [
            'exam' => ['id' => $exam->id, 'title' => $exam->title],
            'attempt' => [
                'id' => $attempt->id,
                'student_index' => $attempt->student_index,
                'student_name' => $attempt->student_fullname,
                'score' => (float) $attempt->score,
                'total_marks' => (int) $attempt->total_marks,
            ],
            'revisionPlan' => $plan ? $this->serializePlan($plan) : null,
        ]);
    }

    private function assertAttemptBelongsToExam(Exam $exam, Attempt $attempt): void
    {
        if ((int) $attempt->exam_id !== (int) $exam->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(RevisionPlan $plan): array
    {
        return [
            'risk_level' => $plan->risk_level,
            'weak_topics' => $plan->weak_topics ?? [],
            'failed_questions' => $plan->failed_questions ?? [],
            'summary' => $plan->summary,
            'revision_notes' => $plan->revision_notes ?? [],
            'flashcards' => $plan->flashcards ?? [],
            'practice_questions' => $plan->practice_questions ?? [],
            'study_plan' => $plan->study_plan ?? [],
            'recommended_actions' => $plan->recommended_actions ?? [],
            'generated_at' => $plan->generated_at?->toIso8601String(),
            'ai_provider' => $plan->ai_provider,
        ];
    }
}
