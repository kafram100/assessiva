<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', [UserRole::Hod]);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
