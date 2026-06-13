<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->role === UserRole::Hod && $user->department_id === null) {
                throw ValidationException::withMessages([
                    'department_id' => ['Heads of department must belong to a department.'],
                ]);
            }
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'status',
        'department_id',
        'school_id',
        'staff_id',
        'phone',
        'approved_at',
        'approved_by',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isHod(): bool
    {
        return $this->role === UserRole::Hod;
    }

    public function isAdminOrHod(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isHod()
            && $this->is_active !== false
            && $this->status === UserStatus::Approved;
    }

    public function isApprovedLecturer(): bool
    {
        return $this->role === UserRole::Lecturer
            && $this->status === UserStatus::Approved
            && $this->is_active !== false;
    }

    public function isApprovedHod(): bool
    {
        return $this->isHod()
            && $this->status === UserStatus::Approved
            && $this->is_active !== false
            && $this->department_id !== null;
    }

    /**
     * When true, AI-generated bank questions from this user must be released by their HoD (not self-approved).
     */
    public function requiresHodAiQuestionReview(): bool
    {
        if (! $this->department_id) {
            return false;
        }

        $this->loadMissing('department');

        return (bool) ($this->department?->require_hod_approval_for_ai_questions ?? false);
    }

    public function canManageExam(Exam $exam): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isApprovedLecturer() && ! $this->isApprovedHod()) {
            return false;
        }

        return (int) $exam->user_id === (int) $this->id;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeAcademicStaff(Builder $query): Builder
    {
        return $query->whereIn('role', [UserRole::Lecturer, UserRole::Hod]);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeLecturers(Builder $query): Builder
    {
        return $query->where('role', UserRole::Lecturer);
    }
}
