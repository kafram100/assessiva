<?php

namespace App\Support;

use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ExamDeviceBinding
{
    public const COOKIE_NAME = 'exam_device_key';

    /**
     * Issue a long-lived device cookie when the student opens the entry page.
     */
    public static function ensureDeviceCookieQueued(Request $request): void
    {
        if ($request->hasCookie(self::COOKIE_NAME)) {
            return;
        }

        Cookie::queue(
            self::COOKIE_NAME,
            (string) Str::uuid(),
            60 * 24 * 90,
            '/',
            null,
            (bool) config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    public static function currentKey(Request $request): string
    {
        return (string) $request->cookie(self::COOKIE_NAME, '');
    }

    /**
     * Whether the browser's device cookie matches the attempt's binding.
     * Legacy attempts with a null key are treated as unbound until the next
     * takeExam visit attaches the current device cookie.
     */
    public static function matchesAttempt(Request $request, Attempt $attempt): bool
    {
        $cookieKey = self::currentKey($request);
        if ($cookieKey === '') {
            return false;
        }

        $bound = $attempt->exam_device_key;
        if ($bound === null || $bound === '') {
            return true;
        }

        return hash_equals($bound, $cookieKey);
    }
}
