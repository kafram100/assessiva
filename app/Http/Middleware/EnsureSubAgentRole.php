<?php

namespace App\Http\Middleware;

use App\Models\SubAgent;
use Closure;
use Illuminate\Http\Request;

class EnsureSubAgentRole
{
    public function handle(Request $request, Closure $next)
    {
        $SubAgentId = (int) $request->session()->get('sub_agent_id');

        if (!$SubAgentId) {
            return redirect()->route('Sub-Agent.login');
        }

        $SubAgent = SubAgent::find($SubAgentId);

        if (!$SubAgent || !$SubAgent->is_active) {
            $request->session()->forget('sub_agent_id');
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            return redirect()->route('Sub-Agent.login')->withErrors([
                'username' => 'Your account has been suspended.',
            ]);
        }

        $request->merge(['sub_agent' => $SubAgent]);
        $request->setUserResolver(function () use ($SubAgent) {
            return $SubAgent;
        });

        return $next($request);
    }
}
