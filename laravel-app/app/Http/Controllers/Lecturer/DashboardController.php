<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();
        $recentExams = Exam::query()
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'course_name', 'exam_code', 'is_published', 'created_at']);

        $examCount = Exam::query()
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return Inertia::render('Lecturer/Dashboard', [
            'recentExams' => $recentExams,
            'examCount' => $examCount,
        ]);
    }
}
