<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class OtpVerificationController extends Controller
{
    private const SESSION_KEY = 'otp_pending_user_id';

    // ── Show OTP entry page ────────────────────────────────────────────────

    public function show(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get(self::SESSION_KEY);

        if (! $userId) {
            return redirect()->route('login')
                ->with('status', 'Please log in to continue.');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('login');
        }

        return Inertia::render('Auth/OtpVerify', [
            'email'      => $user->email,
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    // ── Verify submitted OTP ────────────────────────────────────────────────

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $userId = $request->session()->get(self::SESSION_KEY);

        if (! $userId) {
            return redirect()->route('login')
                ->with('status', 'Session expired. Please log in again.');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('login');
        }

        /** @var EmailOtp|null $record */
        $record = EmailOtp::where('user_id', $user->id)->valid()->first();

        // OTP expired or not found
        if (! $record) {
            return back()->withErrors([
                'otp' => 'Your verification code has expired. Please request a new one.',
            ]);
        }

        // Too many wrong attempts
        if ($record->isLocked()) {
            return back()->withErrors([
                'otp' => 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        // Wrong OTP — increment attempts
        if (! $record->isCorrect($request->otp)) {
            $record->increment('attempts');
            $remaining = 5 - $record->fresh()->attempts;

            return back()->withErrors([
                'otp' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempt(s) remaining."
                    : 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        // ✅ Correct — clean up and log user in
        $record->delete();
        $request->session()->forget(self::SESSION_KEY);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    // ── Resend OTP ──────────────────────────────────────────────────────────

    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get(self::SESSION_KEY);

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('login');
        }

        // Rate-limit resend per user: max 3 per minute
        $throttleKey = 'otp-resend:' . $user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'otp' => "Too many resend requests. Please wait {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $plain = EmailOtp::generate($user);

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($user, $plain));
        } catch (\Throwable $e) {
            Log::error('OTP resend mail failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            return back()->withErrors([
                'otp' => 'Failed to send the verification email. Please check your inbox or try again shortly.',
            ]);
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(3, strlen($local)));
        $stars   = str_repeat('*', max(0, strlen($local) - 3));
        return $visible . $stars . '@' . $domain;
    }
}
