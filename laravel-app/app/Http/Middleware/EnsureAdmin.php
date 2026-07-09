<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        \Illuminate\Support\Facades\Log::info("EnsureAdmin check", ["user_id" => $user?->id, "role" => $user?->role, "isAdmin" => $user?->isAdmin()]);
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Administrator access required.');
        }

        return $next($request);
    }
}
