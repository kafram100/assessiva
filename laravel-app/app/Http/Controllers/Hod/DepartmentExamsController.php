<?php

namespace App\Http\Controllers\Hod;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentExamsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        if (! $user->department_id) {
            return Inertia::render('Hod/Exams/Index', [
                'hasDepartment' => false,
                'exams' => null,
            ]);
        }

        $staffIds = User::query()
            ->where('department_id', $user->department_id)
            ->whereIn('role', [UserRole::Lecturer, UserRole::Hod])
            ->pluck('id');

        $exams = Exam::query()
            ->whereIn('user_id', $staffIds)
            ->with(['owner:id,username,name'])
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Hod/Exams/Index', [
            'hasDepartment' => true,
            'exams' => $exams,
        ]);
    }
}
