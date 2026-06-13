<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Services\LearningTwinService;
use App\Services\PredictionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentIntelligenceController extends Controller
{
    public function learningTwin(Attempt $attempt, LearningTwinService $learningTwinService): Response|RedirectResponse
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            abort(404);
        }

        if (! $exam->results_released) {
            return redirect()
                ->route('student.results.show', $attempt)
                ->with('error', 'Results have not been released yet.');
        }

        $twin = $learningTwinService->findForAttempt($attempt);

        return Inertia::render('Student/Intelligence/LearningTwin', [
            'attempt' => [
                'id' => $attempt->id,
                'student_index' => $attempt->student_index,
            ],
            'exam' => [
                'title' => $exam->title,
                'course_name' => $exam->course_name,
            ],
            'twin' => $twin ? $this->serializeTwin($twin) : null,
        ]);
    }

    public function academicHealth(Attempt $attempt, LearningTwinService $learningTwinService): Response|RedirectResponse
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            abort(404);
        }

        if (! $exam->results_released) {
            return redirect()
                ->route('student.results.show', $attempt)
                ->with('error', 'Results have not been released yet.');
        }

        $twin = $learningTwinService->findForAttempt($attempt);

        return Inertia::render('Student/Intelligence/AcademicHealth', [
            'attempt' => ['id' => $attempt->id],
            'exam' => ['title' => $exam->title],
            'twin' => $twin ? $this->serializeTwin($twin) : null,
        ]);
    }

    public function recommendations(Attempt $attempt, LearningTwinService $learningTwinService, PredictionService $predictionService): Response|RedirectResponse
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            abort(404);
        }

        if (! $exam->results_released) {
            return redirect()
                ->route('student.results.show', $attempt)
                ->with('error', 'Results have not been released yet.');
        }

        $twin = $learningTwinService->findForAttempt($attempt);
        $prediction = $predictionService->latestForAttempt($attempt);

        return Inertia::render('Student/Intelligence/Recommendations', [
            'attempt' => ['id' => $attempt->id],
            'exam' => ['title' => $exam->title],
            'twin' => $twin ? $this->serializeTwin($twin) : null,
            'prediction' => $prediction ? $this->serializePrediction($prediction) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTwin(\App\Models\LearningTwin $t): array
    {
        return [
            'academic_health_score' => $t->academic_health_score,
            'risk_level' => $t->risk_level,
            'predicted_next_score' => $t->predicted_next_score,
            'strengths' => $t->strengths ?? [],
            'weaknesses' => $t->weaknesses ?? [],
            'learning_pattern' => $t->learning_pattern,
            'recommended_actions' => $t->recommended_actions ?? [],
            'revision_focus' => $t->revision_focus ?? [],
            'lecturer_intervention' => $t->lecturer_intervention,
            'generated_at' => $t->generated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePrediction(\App\Models\PredictionReport $r): array
    {
        return [
            'failure_probability' => $r->failure_probability,
            'dropout_risk' => $r->dropout_risk,
            'burnout_risk' => $r->burnout_risk,
            'predicted_grade' => $r->predicted_grade,
            'intervention_urgency' => $r->intervention_urgency,
            'recommended_interventions' => $r->recommended_interventions ?? [],
            'advisory_rationale' => $r->advisory_rationale,
            'generated_at' => $r->generated_at?->toIso8601String(),
        ];
    }
}
