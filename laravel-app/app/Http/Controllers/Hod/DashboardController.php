<?php

namespace App\Http\Controllers\Hod;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\OfficialQuestionReview;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);
        $user->loadMissing('department.school');

        $deptId = $user->department_id;
        $stats = [
            'hasDepartment' => (bool) $deptId,
            'pendingOfficialReviews' => 0,
            'pendingAiReviews' => 0,
            'lecturersInDepartment' => 0,
            'approvedLecturers' => 0,
            'pendingLecturers' => 0,
            'departmentExams' => 0,
            'publishedExams' => 0,
        ];

        if ($deptId) {
            $stats['pendingOfficialReviews'] = OfficialQuestionReview::query()
                ->where('status', 'pending')
                ->whereHas('question', fn ($q) => $q->where('department_id', $deptId))
                ->count();

            $stats['pendingAiReviews'] = QuestionBank::query()
                ->where('generated_by_ai', true)
                ->where('submitted_for_admin_ai_review', true)
                ->where('status', 'draft')
                ->whereHas('lecturer', fn ($q) => $q->where('department_id', $deptId))
                ->count();

            $lecturerBase = User::query()
                ->where('department_id', $deptId)
                ->where('role', UserRole::Lecturer);

            $stats['lecturersInDepartment'] = (clone $lecturerBase)->count();
            $stats['approvedLecturers'] = (clone $lecturerBase)
                ->where('status', UserStatus::Approved)
                ->where('is_active', true)
                ->count();
            $stats['pendingLecturers'] = (clone $lecturerBase)
                ->where('status', UserStatus::Pending)
                ->count();

            $staffIds = User::query()
                ->where('department_id', $deptId)
                ->whereIn('role', [UserRole::Lecturer, UserRole::Hod])
                ->pluck('id');

            $stats['departmentExams'] = Exam::query()
                ->whereIn('user_id', $staffIds)
                ->count();
            $stats['publishedExams'] = Exam::query()
                ->whereIn('user_id', $staffIds)
                ->where('is_published', true)
                ->count();
        }

        return Inertia::render('Hod/Dashboard', [
            'department' => $user->department,
            'stats' => $stats,
        ]);
    }
}
