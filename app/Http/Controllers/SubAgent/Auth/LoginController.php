<?php

namespace App\Http\Controllers\SubAgent\Auth;

use App\Http\Controllers\Controller;
use App\Models\SubAgent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('SubAgent/Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required_without_all:email,phone|string',
            'email' => 'required_without_all:username,phone|email',
            'phone' => 'required_without_all:username,email|string',
            'password' => 'required|string',
        ]);

        $field = match (true) {
            !empty($credentials['email']) => 'email',
            !empty($credentials['phone']) => 'phone',
            default => 'username',
        };

        $SubAgent = SubAgent::where($field, $credentials[$field])->first();

        if (!$SubAgent || !password_verify($credentials['password'], $SubAgent->password_hash)) {
            return back()->withErrors([
                $field => 'The provided credentials do not match our records.',
            ]);
        }

        if (!$SubAgent->is_active) {
            return back()->withErrors([
                $field => 'Your account has been suspended.',
            ]);
        }

        session(['sub_agent_id' => $SubAgent->id]);
        session()->regenerate();

        return redirect()->route('Sub-Agent.dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget('sub_agent_id');
        session()->regenerate();
        session()->regenerateToken();

        return redirect()->route('Sub-Agent.login');
    }
}
