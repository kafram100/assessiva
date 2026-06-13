<?php

namespace App\Http\Controllers\Hod;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LecturerDirectoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        if (! $user->department_id) {
            return Inertia::render('Hod/Lecturers/Index', [
                'hasDepartment' => false,
                'lecturers' => null,
            ]);
        }

        $lecturers = User::query()
            ->where('department_id', $user->department_id)
            ->where('role', UserRole::Lecturer)
            ->withCount('exams')
            ->orderBy('username')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Hod/Lecturers/Index', [
            'hasDepartment' => true,
            'lecturers' => $lecturers,
        ]);
    }
}
