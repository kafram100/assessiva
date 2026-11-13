<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('login');
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : (preg_match('/^[0-9\-\+\s\(\)]+$/', $login) ? 'phone' : 'username');

        if (! Auth::attempt([$loginType => $this->input('login'), 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();
        if ($user instanceof User && $user->role !== UserRole::SuperAdmin) {
            if ($user->is_active === false) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account has been deactivated. Contact an administrator.',
                ]);
            }
            if ($user->status === UserStatus::Rejected) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account has been rejected. Contact an administrator.',
                ]);
            }
            if ($user->status === UserStatus::Suspended) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account has been suspended. Contact an administrator.',
                ]);
            }
            if ($user->status !== UserStatus::Approved) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account is pending approval. You will be able to log in once an administrator approves it.',
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
