<?php

namespace App\Http\Controllers\Reports;

use App\Exports\DepartmentAnalyticsExport;
use App\Exports\ExamResultsExport;
use App\Exports\FairnessReportExport;
use App\Exports\InstitutionalAnalyticsExport;
use App\Exports\LearningTwinExport;
use App\Exports\PredictionReportExport;
use App\Exports\PracticeQuizAnalyticsExport;
use App\Exports\ProctoringReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\LearningTwin;
use App\Services\AdminInstitutionalIntelligenceService;
use App\Services\HodAcademicIntelligenceService;
use App\Services\LearningTwinService;
use App\Services\PredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExportController extends Controller
{
    private function wantsExcelErrorFeedback(Request $request): bool
    {
        return $request->header('X-Excel-Export') === '1';
    }

    private function jsonError(Request $request, string $message, int $status = 422): JsonResponse
    {
        if ($this->wantsExcelErrorFeedback($request)) {
            return response()->json(['message' => $message], $status);
        }

        abort($status, $message);
    }

    public function exportExamResults(Request $request, Exam $exam): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $exam);

        try {
            $slug = Str::slug($exam->exam_code ?: $exam->title ?: 'exam', '_');
            $filename = "{$slug}_results_".now()->format('Ymd_His').'.xlsx';

            return Excel::download(new ExamResultsExport($exam), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the Excel file. Try again or contact support.', 500);
        }
    }

    public function exportFairnessReport(Request $request, Exam $exam): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('update', $exam);
        $exam->load('fairnessReport');
        if (! $exam->fairnessReport) {
            return $this->jsonError($request, 'Run fairness analysis first, then export again.', 422);
        }

        try {
            $slug = Str::slug($exam->exam_code ?: $exam->title ?: 'exam', '_');
            $filename = "fairness_report_{$slug}_".now()->format('Ymd_His').'.xlsx';

            return Excel::download(new FairnessReportExport($exam, $exam->fairnessReport), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the fairness export.', 500);
        }
    }

    public function exportLearningTwin(Request $request, Exam $exam, LearningTwinService $learningTwinService): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $exam);
        $user = $request->user();

        $data = $request->validate([
            'student_index' => 'required|string|max:64',
            'course_id' => 'nullable|integer|exists:courses,id',
        ]);

        $idx = ExamAllowedIndex::normalize($data['student_index']);
        if ($idx === '') {
            return $this->jsonError($request, 'Invalid student index.', 422);
        }

        if (! $this->studentOnExamRoster($exam, $idx)) {
            return $this->jsonError($request, 'That student index is not linked to this exam (roster or attempts).', 403);
        }

        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;
        if ($exam->course_id !== null && (int) $exam->course_id > 0) {
            if ($courseId !== null && $courseId !== (int) $exam->course_id) {
                return $this->jsonError($request, 'Course scope must match this exam\'s course.', 422);
            }
            $courseId = (int) $exam->course_id;
        } elseif ($courseId !== null) {
            $course = Course::query()->findOrFail($courseId);
            Gate::authorize('view', $course);
            if ((int) $course->user_id !== (int) $user->id) {
                return $this->jsonError($request, 'You can only export learning twin data for courses you own.', 403);
            }
        }

        $twin = LearningTwin::query()
            ->where('lecturer_user_id', $user->id)
            ->where('student_index', $idx)
            ->where('context_key', $learningTwinService->contextKey($courseId))
            ->first();

        $courseName = $courseId !== null
            ? (string) Course::query()->whereKey($courseId)->value('name')
            : null;

        try {
            $slug = Str::slug($exam->exam_code ?: $exam->title ?: 'exam', '_');
            $filename = "learning_twin_{$slug}_".Str::slug($idx, '_').'_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(new LearningTwinExport($twin, $idx, $courseName), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the learning twin export.', 500);
        }
    }

    public function exportPredictionReport(Request $request, Exam $exam, PredictionService $predictionService): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $exam);
        $user = $request->user();

        $data = $request->validate([
            'student_index' => 'required|string|max:64',
            'course_id' => 'nullable|integer|exists:courses,id',
        ]);

        $idx = ExamAllowedIndex::normalize($data['student_index']);
        if ($idx === '') {
            return $this->jsonError($request, 'Invalid student index.', 422);
        }

        if (! $this->studentOnExamRoster($exam, $idx)) {
            return $this->jsonError($request, 'That student index is not linked to this exam (roster or attempts).', 403);
        }

        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;
        if ($exam->course_id !== null && (int) $exam->course_id > 0) {
            if ($courseId !== null && $courseId !== (int) $exam->course_id) {
                return $this->jsonError($request, 'Course scope must match this exam\'s course.', 422);
            }
            $courseId = (int) $exam->course_id;
        } elseif ($courseId !== null) {
            $course = Course::query()->findOrFail($courseId);
            Gate::authorize('view', $course);
            if ((int) $course->user_id !== (int) $user->id) {
                return $this->jsonError($request, 'You can only export prediction data for courses you own.', 403);
            }
        }

        $report = $predictionService->latestForStudent($user, $data['student_index'], $courseId);

        $courseName = $courseId !== null
            ? (string) Course::query()->whereKey($courseId)->value('name')
            : null;

        try {
            $slug = Str::slug($exam->exam_code ?: $exam->title ?: 'exam', '_');
            $filename = "prediction_report_{$slug}_".Str::slug($idx, '_').'_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(new PredictionReportExport($report, $idx, $courseName), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the prediction export.', 500);
        }
    }

    public function exportPracticeAnalytics(Request $request, Exam $quiz): BinaryFileResponse|JsonResponse
    {
        if (! $quiz->practice_mode) {
            return $this->jsonError($request, 'Not a practice quiz.', 404);
        }
        Gate::authorize('update', $quiz);

        try {
            $slug = Str::slug($quiz->title ?: 'practice_quiz', '_');
            $filename = "practice_analytics_{$slug}_".now()->format('Ymd_His').'.xlsx';

            return Excel::download(PracticeQuizAnalyticsExport::fromQuiz($quiz), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the practice analytics export.', 500);
        }
    }

    public function exportDepartmentAnalytics(Request $request, HodAcademicIntelligenceService $hodAcademicIntelligence): BinaryFileResponse|JsonResponse
    {
        $user = $request->user();
        if (! $user->isHod()) {
            abort(403);
        }

        $payload = $hodAcademicIntelligence->dashboard($user);
        $label = (string) ($payload['department']['name'] ?? 'department');
        $deptSlug = Str::slug($label, '_') ?: 'department';

        try {
            $filename = "department_analytics_{$deptSlug}_".now()->format('Ymd_His').'.xlsx';

            return Excel::download(new DepartmentAnalyticsExport($payload, $label), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the department analytics export.', 500);
        }
    }

    public function exportInstitutionalAnalytics(Request $request, AdminInstitutionalIntelligenceService $svc): BinaryFileResponse|JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            abort(403);
        }

        $dashboard = $svc->dashboard();

        try {
            $filename = 'institutional_analytics_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(new InstitutionalAnalyticsExport($dashboard), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the institutional analytics export.', 500);
        }
    }

    public function exportProctoringReport(Request $request): BinaryFileResponse|JsonResponse
    {
        $user = $request->user();
        $adminScope = $user->isAdmin();

        if (! $adminScope && ! $user->isApprovedLecturer() && ! $user->isApprovedHod()) {
            abort(403);
        }

        [$attemptIds, $maxRows, $filters] = ProctoringReportExport::resolveAttemptContext($request, $user, $adminScope);

        try {
            $who = $adminScope ? 'institution' : 'staff_'.$user->id;
            $filename = 'proctoring_report_'.Str::slug($who, '_').'_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(new ProctoringReportExport($attemptIds, $filters, $maxRows), $filename);
        } catch (Throwable) {
            return $this->jsonError($request, 'Could not generate the proctoring export.', 500);
        }
    }

    private function studentOnExamRoster(Exam $exam, string $normalizedIndex): bool
    {
        if (ExamAllowedIndex::query()->where('exam_id', $exam->id)->where('student_index', $normalizedIndex)->exists()) {
            return true;
        }

        return Attempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_index', $normalizedIndex)
            ->exists();
    }
}
