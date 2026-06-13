<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\Exam;
use App\Models\ProctoringPenaltyRule;
use App\Services\ProctoringPenaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProctoringPenaltyController extends Controller
{
    public function __construct(
        private ProctoringPenaltyService $penaltyService
    ) {}

    public function index(Exam $exam): Response
    {
        Gate::authorize('view', $exam);

        return Inertia::render('Lecturer/Proctoring/PenaltyRules', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'exam_code' => $exam->exam_code,
            ],
            'rules' => $this->penaltyService->rulesPayloadForExam($exam),
            'eventTypes' => ProctoringPenaltyService::supportedEventTypes(),
            'reviewStatuses' => [
                ProctoringPenaltyService::STATUS_PENDING,
                ProctoringPenaltyService::STATUS_APPLIED,
                ProctoringPenaltyService::STATUS_IGNORED,
                ProctoringPenaltyService::STATUS_DISMISSED,
            ],
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('view', $exam);

        $data = $request->validate([
            'rules' => 'required|array',
            'rules.*.event_type' => 'required|string|max:80',
            'rules.*.penalty_marks' => 'nullable|numeric|min:0',
            'rules.*.is_enabled' => 'nullable|boolean',
            'rules.*.severity' => 'nullable|string|max:32',
            'rules.*.description' => 'nullable|string|max:2000',
        ]);

        $this->penaltyService->upsertRules($exam, $data['rules'], (int) $request->user()->id);

        return redirect()
            ->route('lecturer.exams.proctoring.penalties.index', $exam)
            ->with('success', 'Proctoring penalty rules saved.');
    }

    public function update(Request $request, Exam $exam, ProctoringPenaltyRule $rule): RedirectResponse
    {
        Gate::authorize('view', $exam);
        if ((int) $rule->exam_id !== (int) $exam->id) {
            abort(404);
        }

        $data = $request->validate([
            'penalty_marks' => 'nullable|numeric|min:0',
            'is_enabled' => 'nullable|boolean',
            'severity' => 'nullable|string|max:32',
            'description' => 'nullable|string|max:2000',
        ]);

        $this->penaltyService->updateRule($rule, $data, (int) $request->user()->id);

        return back()->with('success', 'Rule updated.');
    }

    public function applyToEvent(Request $request, Exam $exam, CheatEvent $event): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $exam);
        $this->assertEventBelongsToExam($exam, $event);

        $this->penaltyService->applyPenaltyToEvent($event, (int) $request->user()->id);

        return $this->respondProctoringAction($request, 'Penalty applied to event.');
    }

    public function ignoreEvent(Request $request, Exam $exam, CheatEvent $event): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $exam);
        $this->assertEventBelongsToExam($exam, $event);

        $this->penaltyService->ignoreEvent($event, (int) $request->user()->id, ProctoringPenaltyService::STATUS_IGNORED);

        return $this->respondProctoringAction($request, 'Event marked as ignored (false positive).');
    }

    public function dismissEvent(Request $request, Exam $exam, CheatEvent $event): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $exam);
        $this->assertEventBelongsToExam($exam, $event);

        $this->penaltyService->markReviewedNoPenalty($event, (int) $request->user()->id);

        return $this->respondProctoringAction($request, 'Event dismissed (reviewed, no penalty).');
    }

    public function applyAllReviewed(Request $request, Exam $exam, Attempt $attempt): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $exam);
        if ((int) $attempt->exam_id !== (int) $exam->id) {
            abort(404);
        }

        $data = $request->validate([
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'integer',
        ]);

        $n = $this->penaltyService->applyPenaltyToEvents($exam, $attempt, $data['event_ids'], (int) $request->user()->id);

        return $this->respondProctoringAction(
            $request,
            "Penalties applied to {$n} event(s)."
        );
    }

    public function penaltySummary(Exam $exam, Attempt $attempt): JsonResponse
    {
        Gate::authorize('view', $exam);
        if ((int) $attempt->exam_id !== (int) $exam->id) {
            abort(404);
        }

        return response()->json($this->penaltyService->penaltySummary($attempt));
    }

    private function assertEventBelongsToExam(Exam $exam, CheatEvent $event): void
    {
        $event->loadMissing('attempt');
        if (! $event->attempt || (int) $event->attempt->exam_id !== (int) $exam->id) {
            abort(404);
        }
    }

    private function respondProctoringAction(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
