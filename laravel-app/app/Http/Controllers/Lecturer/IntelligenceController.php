<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\LearningTwin;
use App\Services\AnalyticsIntelligenceService;
use App\Services\LearningTwinService;
use App\Services\PredictionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IntelligenceController extends Controller
{
    public function learningTwin(Request $request, LearningTwinService $learningTwinService): Response
    {
        $user = $request->user();
        $courses = Course::query()->where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);
        $exams = Exam::query()
            ->where('user_id', $user->id)
            ->orderBy('title')
            ->get(['id', 'title', 'course_id', 'exam_code']);

        $studentIndex = (string) $request->query('student_index', '');
        $courseId = $request->query('course_id');
        $courseId = $courseId === null || $courseId === '' ? null : (int) $courseId;

        $twin = null;
        if ($studentIndex !== '') {
            $idx = ExamAllowedIndex::normalize($studentIndex);
            if ($idx !== '') {
                $twin = LearningTwin::query()
                    ->where('lecturer_user_id', $user->id)
                    ->where('student_index', $idx)
                    ->where('context_key', $learningTwinService->contextKey($courseId))
                    ->first();
            }
        }

        return Inertia::render('Lecturer/Intelligence/LearningTwinDashboard', [
            'courses' => $courses,
            'exams' => $exams,
            'twin' => $twin ? $this->serializeLearningTwin($twin) : null,
            'filters' => [
                'student_index' => $studentIndex,
                'course_id' => $courseId,
            ],
        ]);
    }

    public function learningTwinGenerate(Request $request, LearningTwinService $learningTwinService): RedirectResponse
    {
        $user = $request->user();
        $request->merge([
            'course_id' => $request->filled('course_id') ? (int) $request->input('course_id') : null,
        ]);
        $data = $request->validate([
            'student_index' => 'required|string|max:64',
            'course_id' => 'nullable|integer|exists:courses,id',
        ]);
        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;
        if ($courseId !== null) {
            $course = Course::query()->findOrFail($courseId);
            Gate::authorize('view', $course);
            if ((int) $course->user_id !== (int) $user->id) {
                abort(403);
            }
        }

        try {
            $learningTwinService->generateAndStore($user, $data['student_index'], $courseId, true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not generate learning twin: '.$e->getMessage())->withInput();
        }

        return redirect()->route('lecturer.intelligence.learning-twin', [
            'student_index' => $data['student_index'],
            'course_id' => $courseId,
        ])->with('success', 'Learning twin generated.');
    }

    public function prediction(Request $request, PredictionService $predictionService): Response
    {
        $user = $request->user();
        $courses = Course::query()->where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);
        $exams = Exam::query()
            ->where('user_id', $user->id)
            ->orderBy('title')
            ->get(['id', 'title', 'course_id', 'exam_code']);

        $studentIndex = (string) $request->query('student_index', '');
        $courseId = $request->query('course_id');
        $courseId = $courseId === null || $courseId === '' ? null : (int) $courseId;

        $report = null;
        if ($studentIndex !== '') {
            $report = $predictionService->latestForStudent($user, $studentIndex, $courseId);
        }

        return Inertia::render('Lecturer/Intelligence/PredictionDashboard', [
            'courses' => $courses,
            'exams' => $exams,
            'prediction' => $report ? $this->serializePrediction($report) : null,
            'filters' => [
                'student_index' => $studentIndex,
                'course_id' => $courseId,
            ],
        ]);
    }

    public function predictionGenerate(Request $request, PredictionService $predictionService): RedirectResponse
    {
        $user = $request->user();
        $request->merge([
            'course_id' => $request->filled('course_id') ? (int) $request->input('course_id') : null,
        ]);
        $data = $request->validate([
            'student_index' => 'required|string|max:64',
            'course_id' => 'nullable|integer|exists:courses,id',
        ]);
        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;
        if ($courseId !== null) {
            $course = Course::query()->findOrFail($courseId);
            Gate::authorize('view', $course);
            if ((int) $course->user_id !== (int) $user->id) {
                abort(403);
            }
        }

        try {
            $predictionService->generateAndStore($user, $data['student_index'], $courseId);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not run prediction: '.$e->getMessage())->withInput();
        }

        return redirect()->route('lecturer.intelligence.prediction', [
            'student_index' => $data['student_index'],
            'course_id' => $courseId,
        ])->with('success', 'Prediction report generated.');
    }

    public function analytics(Request $request, AnalyticsIntelligenceService $analyticsService): Response
    {
        $user = $request->user();
        $courses = Course::query()->where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);
        $courseId = $request->query('course_id');
        $courseId = $courseId === null || $courseId === '' ? null : (int) $courseId;
        $report = null;
        if ($courseId !== null) {
            $report = $analyticsService->latestForCourse($user, $courseId);
        }

        return Inertia::render('Lecturer/Intelligence/AnalyticsDashboard', [
            'courses' => $courses,
            'report' => $report ? $this->serializeAnalytics($report) : null,
            'filters' => ['course_id' => $courseId],
        ]);
    }

    public function analyticsGenerate(Request $request, AnalyticsIntelligenceService $analyticsService): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);
        $course = Course::query()->findOrFail($data['course_id']);
        Gate::authorize('view', $course);
        if ((int) $course->user_id !== (int) $user->id) {
            abort(403);
        }

        try {
            $analyticsService->generateAndStore($user, $course, 'course');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not run analytics: '.$e->getMessage())->withInput();
        }

        return redirect()->route('lecturer.intelligence.analytics', array_filter([
            'course_id' => $course->id,
        ], fn ($v) => $v !== null && $v !== ''))->with('success', 'Analytics intelligence generated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLearningTwin(LearningTwin $t): array
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
            'student_data_snapshot' => $r->student_data_snapshot ?? [],
            'generated_at' => $r->generated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnalytics(\App\Models\AnalyticsReport $r): array
    {
        return [
            'course_health_score' => $r->course_health_score,
            'at_risk_students' => $r->at_risk_students ?? [],
            'weak_topics' => $r->weak_topics ?? [],
            'performance_clusters' => $r->performance_clusters ?? [],
            'interventions' => $r->interventions ?? [],
            'summary' => $r->summary ?? [],
            'department_insights' => $r->department_insights ?? [],
            'cohort_trends' => $r->cohort_trends ?? [],
            'generated_at' => $r->generated_at?->toIso8601String(),
        ];
    }
}
