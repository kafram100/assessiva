<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentOverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        $exams = Exam::query()
            ->where('user_id', $userId)
            ->withCount('attempts')
            ->orderByDesc('id')
            ->get();

        $stats = Attempt::query()
            ->whereHas('exam', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'completed')
            ->selectRaw('COUNT(DISTINCT student_index) as unique_students, COUNT(*) as total_attempts, AVG(score) as avg_score, MAX(score) as max_score')
            ->first();

        return Inertia::render('Lecturer/Students/Index', [
            'exams' => $exams,
            'stats' => [
                'unique_students' => (int) ($stats->unique_students ?? 0),
                'total_attempts' => (int) ($stats->total_attempts ?? 0),
                'avg_score' => $stats->avg_score !== null ? round((float) $stats->avg_score, 2) : null,
                'max_score' => $stats->max_score !== null ? (float) $stats->max_score : null,
            ],
        ]);
    }
}
