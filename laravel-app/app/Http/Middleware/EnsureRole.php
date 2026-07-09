<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  string  ...$roles  Allowed role values, e.g. hod, lecturer, admin
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $allowed = [];
        foreach ($roles as $roleGroup) {
            foreach (explode(',', $roleGroup) as $role) {
                $allowed[] = strtolower(trim($role));
            }
        }

        if (! in_array($user->role->value, $allowed, true)) {
            \Illuminate\Support\Facades\Log::error('EnsureRole 403', [
                'user_role' => $user->role->value,
                'allowed' => $allowed,
                'roles_raw' => func_get_args(),
            ]);
            abort(403, 'You do not have permission for this action.');
        }

        return $next($request);
    }
}
