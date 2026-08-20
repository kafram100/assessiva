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
        'free_questions_remaining',
        'tokens',
        'level',
        'educational_level',
        'profile_picture',
        'permissions',
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
            'permissions' => 'array',
        ];
    }

    protected $appends = ['profile_picture_url'];


    public function getProfilePictureUrlAttribute(): ?string
    {
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }
        return null;
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function practiceSessions()
    {
        return $this->hasMany(UserPracticeSession::class);
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

    public function isApprovedHod(): bool
    {
        return $this->isHod();
    }

    public function isAdminOrHod(): bool
    {
        return $this->isAdmin() || $this->isHod();
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        // Admin ID 1 is the Super Admin and has all permissions
        if ($this->id === 1) {
            return true;
        }

        if (empty($this->permissions)) {
            return true;
        }

        return in_array($permission, $this->permissions, true) || in_array('*', $this->permissions, true);
    }

    public function isHod(): bool
    {
        return false;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function requiresHodAiQuestionReview(): bool
    {
        // The require_hod_approval_for_ai_questions column was dropped.
        // Returning false by default to restore functionality without errors.
        return false;
    }

    public function canManageExam(Exam $exam): bool
    {
        return $this->isAdmin();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', UserRole::Student);
    }
}
