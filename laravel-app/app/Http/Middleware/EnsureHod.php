<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHod
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isHod()) {
            abort(403, 'Head of Department access required.');
        }

        if (! $user->is_active || $user->status !== UserStatus::Approved) {
            abort(403, 'Your HOD account is not active. Contact an administrator.');
        }

        if (! $user->department_id) {
            return redirect()
                ->route('profile.edit')
                ->with(
                    'error',
                    'Your Head of Department account must be linked to a department before you can use the HOD workspace. Ask an administrator to assign your department.',
                );
        }

        return $next($request);
    }
}