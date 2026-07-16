<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class EnsureApiIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isActive = AppSetting::getValue('api_status', '1');
        
        if ($isActive !== '1') {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The system is currently undergoing maintenance. API is offline. Please try again later.',
                    'error' => 'The system is currently undergoing maintenance. API is offline. Please try again later.'
                ], 503);
            }
            
            throw ValidationException::withMessages([
                'phone' => 'The system is currently undergoing maintenance. API is offline. Please try again later.',
                'error' => 'The system is currently undergoing maintenance. API is offline. Please try again later.',
                'package_id' => 'The system is currently undergoing maintenance. API is offline. Please try again later.'
            ]);
        }

        return $next($request);
    }
}
