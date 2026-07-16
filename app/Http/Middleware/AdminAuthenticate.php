<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminAuthenticate extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);
            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());
        $user = $guard->user();
        $panel = Filament::getCurrentPanel();

        if ($user instanceof FilamentUser) {
            if (! $user->canAccessPanel($panel)) {
                if ($user->role === 'agent' || $user->role === 'dealer') {
                    throw new HttpResponseException(redirect()->to('/dashboard'));
                }
                
                $guard->logout();
                $request->session()->regenerate();
                $request->session()->regenerateToken();
                throw new HttpResponseException(redirect()->to('/admin/login')->withErrors(['Your account is suspended or unauthorized.']));
            }
        }
    }
}
