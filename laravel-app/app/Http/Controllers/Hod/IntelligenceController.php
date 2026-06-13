<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsReport;
use App\Models\Course;
use App\Services\AnalyticsIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IntelligenceController extends Controller
{
    public function institutional(Request $request): Response
    {
        $reports = AnalyticsReport::query()
            ->where('owner_user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return Inertia::render('Hod/Intelligence/Institutional', [
            'reports' => $reports->map(fn (AnalyticsReport $r) => $this->serializeReport($r))->all(),
        ]);
    }

    public function atRisk(Request $request): Response
    {
        $rows = [];
        $reports = AnalyticsReport::query()
            ->where('owner_user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        foreach ($reports as $r) {
            foreach ($r->at_risk_students ?? [] as $item) {
                $rows[] = array_merge((array) $item, [
                    'course' => $r->course_name,
                    'generated_at' => $r->generated_at?->toIso8601String(),
                ]);
            }
        }

        return Inertia::render('Hod/Intelligence/AtRisk', [
            'atRiskRows' => $rows,
        ]);
    }

    public function department(Request $request): Response
    {
        $user = $request->user();
        $deptId = (int) ($user->department_id ?? 0);
        $reports = AnalyticsReport::query()
            ->where('owner_user_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return Inertia::render('Hod/Intelligence/Department', [
            'reports' => $reports->map(fn (AnalyticsReport $r) => $this->serializeReport($r))->all(),
            'departmentId' => $deptId,
        ]);
    }

    public function cohort(Request $request): Response
    {
        $user = $request->user();
        $deptId = (int) ($user->department_id ?? 0);
        $courses = Course::query()
            ->when($deptId > 0, fn ($q) => $q->where('department_id', $deptId))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);

        $courseId = $request->query('course_id');
        $courseId = $courseId === null || $courseId === '' ? null : (int) $courseId;
        $report = null;
        if ($courseId !== null) {
            $report = AnalyticsReport::query()
                ->where('owner_user_id', $user->id)
                ->where('course_id', $courseId)
                ->orderByDesc('id')
                ->first();
        }

        return Inertia::render('Hod/Intelligence/Cohort', [
            'courses' => $courses,
            'report' => $report ? $this->serializeReport($report) : null,
            'filters' => ['course_id' => $courseId],
        ]);
    }

    public function analyticsGenerate(Request $request, AnalyticsIntelligenceService $analytics): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);
        $course = Course::query()->findOrFail($data['course_id']);
        Gate::authorize('view', $course);

        try {
            $analytics->generateAndStore($user, $course, 'course');
        } catch (\Throwable $e) {
            return back()->with('error', 'Analytics failed: '.$e->getMessage())->withInput();
        }

        return redirect()->route('hod.intelligence.cohort', ['course_id' => $course->id])
            ->with('success', 'Analytics generated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReport(AnalyticsReport $r): array
    {
        return [
            'id' => $r->id,
            'course_name' => $r->course_name,
            'department_name' => $r->department_name,
            'course_health_score' => $r->course_health_score,
            'at_risk_students' => $r->at_risk_students ?? [],
            'weak_topics' => $r->weak_topics ?? [],
            'performance_clusters' => $r->performance_clusters ?? [],
            'interventions' => $r->interventions ?? [],
            'summary' => $r->summary ?? [],
            'department_insights' => $r->department_insights ?? [],
            'cohort_trends' => $r->cohort_trends ?? [],
            'cohort_students_snapshot' => $r->cohort_students_snapshot ?? [],
            'generated_at' => $r->generated_at?->toIso8601String(),
        ];
    }
}
