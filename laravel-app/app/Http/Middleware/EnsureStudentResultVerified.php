<?php

namespace App\Http\Middleware;

use App\Models\Attempt;
use App\Models\ExamAllowedIndex;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the browser session proves the student looked up this attempt
 * (exam code + index) or is mid-flow from the same exam session.
 */
class EnsureStudentResultVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $attempt = $request->route('attempt');
        if (! $attempt instanceof Attempt) {
            abort(404);
        }

        if (! $this->sessionOwnsAttempt($attempt)) {
            return redirect()
                ->route('student.results.lookup-form')
                ->with('error', 'Look up your results with your exam code and index number first.');
        }

        return $next($request);
    }

    private function sessionOwnsAttempt(Attempt $attempt): bool
    {
        $verifiedId = (int) session('student_results_verified_attempt_id', 0);
        if ($verifiedId === (int) $attempt->id) {
            return true;
        }

        $examId = (int) session('exam_id', 0);
        $sessionAttemptId = (int) session('exam_attempt_id', 0);
        $idx = ExamAllowedIndex::normalize((string) session('student_index', ''));

        if (
            $sessionAttemptId === (int) $attempt->id
            && $examId === (int) $attempt->exam_id
            && $idx !== ''
            && $idx === ExamAllowedIndex::normalize((string) $attempt->student_index)
        ) {
            return true;
        }

        return false;
    }
}
