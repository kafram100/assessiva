<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks non-admin staff whose account is pending, rejected, suspended, or deactivated.
 */
class EnsureApprovedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->is_active) {
            return $this->logoutAndRedirect($request, 'Your account has been deactivated. Contact an administrator.');
        }

        if ($user->status === UserStatus::Pending) {
            return $this->logoutAndRedirect($request, 'Your account is still pending approval.');
        }

        if ($user->status === UserStatus::Rejected) {
            return $this->logoutAndRedirect($request, 'Your account has been rejected. Contact an administrator.');
        }

        if ($user->status === UserStatus::Suspended) {
            return $this->logoutAndRedirect($request, 'Your account has been suspended. Contact an administrator.');
        }

        if ($user->status !== UserStatus::Approved) {
            return $this->logoutAndRedirect($request, 'Your account cannot access this area.');
        }

        return $next($request);
    }

    private function logoutAndRedirect(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', $message);
    }
}
