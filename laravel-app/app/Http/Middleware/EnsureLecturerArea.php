<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lecturer workspace: approved lecturers, approved HoDs (department-scoped courses), or admins.
 */
class EnsureLecturerArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if ($user->isAdmin() || $user->isApprovedLecturer() || $user->isApprovedHod()) {
            return $next($request);
        }

        abort(403, 'You do not have access to the lecturer area. Approved lecturers, heads of department, or administrators only.');
    }
}
