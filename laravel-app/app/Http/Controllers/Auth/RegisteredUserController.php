<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/', 'not_regex:/super\s*admin/i'],
            'email'             => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone'             => ['required', 'string', 'regex:/^0[0-9]{9}$/', 'unique:'.User::class],
            'educational_level' => ['required', 'string', 'in:JHS,SHS,Tertiary'],
            'password'          => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = new User;
        $user->fill([
            'username'          => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['name'])) . rand(100, 999),
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'],
            'educational_level' => $validated['educational_level'],
        ]);
        $user->password                 = Hash::make($validated['password']);
        $user->role                     = UserRole::User;
        $user->status                   = UserStatus::Approved;
        $user->is_active                = true;
        $user->free_questions_remaining = 20;
        $user->tokens                   = 0;
        $user->save();

        // Generate OTP, send email, and redirect to OTP verification page
        $plain = EmailOtp::generate($user);

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($user, $plain));
        } catch (\Throwable $e) {
            Log::error('OTP mail send failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            // Still redirect to OTP page — user can use Resend once SMTP is fixed
        }

        $request->session()->put('otp_pending_user_id', $user->id);

        return redirect()->route('otp.verify');
    }
}
