<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $login = trim((string) $this->input('login'));
        $password = (string) $this->input('password');

        $user = User::where(function ($query) use ($login) {
            $query->whereRaw('LOWER(email) = ?', [strtolower($login)])
                  ->orWhereRaw('LOWER(username) = ?', [strtolower($login)]);
            if (preg_match('/^[0-9\-\+\s\(\)]+$/', $login)) {
                $query->orWhere('phone', $login);
            }
        })->first();

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'No account was found with that email or username. Please check your spelling.',
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'password' => 'The password you entered is incorrect. Please try again.',
            ]);
        }

        if ($user->role !== UserRole::SuperAdmin) {
            if ($user->is_active === false) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account has been deactivated. Contact an administrator.',
                ]);
            }
            if ($user->status === UserStatus::Rejected) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account has been rejected. Contact an administrator.',
                ]);
            }
            if ($user->status === UserStatus::Suspended) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account has been suspended. Contact an administrator.',
                ]);
            }
            if ($user->status !== UserStatus::Approved) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'Your account is pending approval. You will be able to log in once an administrator approves it.',
                ]);
            }
        }

        Auth::login($user, $this->boolean('remember'));

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
