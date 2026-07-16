<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectNonAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            if ($user->role === 'agent') {
                return redirect()->to('/dashboard');
            } elseif ($user->role === 'dealer') {
                return redirect()->to('/dashboard');
            }

            auth()->logout();
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            return redirect()->to('/admin/login');
        }

        if ($user->status !== 'active') {
            auth()->logout();
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            return redirect()->to('/admin/login')->withErrors(['Your account has been suspended.']);
        }

        return $next($request);
    }
}
