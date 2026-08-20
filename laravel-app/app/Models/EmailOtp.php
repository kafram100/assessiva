<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EmailOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp_hash',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Scope: only OTPs that have not yet expired */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Generate a new OTP for the given user.
     *
     * Deletes any previous OTPs for that user, creates a new record with a
     * hashed 6-digit code, and returns the plain-text code to be emailed.
     */
    public static function generate(User $user): string
    {
        // Remove all previous OTPs for this user
        static::where('user_id', $user->id)->delete();

        $plain = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        static::create([
            'user_id'    => $user->id,
            'otp_hash'   => hash('sha256', $plain),
            'expires_at' => Carbon::now()->addSeconds(120),
            'attempts'   => 0,
        ]);

        return $plain;
    }

    /** Check a submitted plain OTP against the stored hash */
    public function isCorrect(string $plain): bool
    {
        return hash('sha256', $plain) === $this->otp_hash;
    }

    /** Whether this OTP record has reached the attempt limit */
    public function isLocked(): bool
    {
        return $this->attempts >= 5;
    }
}
