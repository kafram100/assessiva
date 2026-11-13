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

    protected $appends = ['profile_picture_url', 'is_super_admin'];

    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }

        if (str_starts_with($this->profile_picture, 'http://') || str_starts_with($this->profile_picture, 'https://')) {
            return $this->profile_picture;
        }

        $clean = ltrim($this->profile_picture, '/');
        if (str_starts_with($clean, 'storage/')) {
            $clean = substr($clean, 8);
        }

        $fullPath = storage_path('app/public/' . $clean);
        if (!file_exists($fullPath)) {
            return null;
        }

        $version = filemtime($fullPath);

        return asset('storage/' . $clean) . '?v=' . $version;
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->isSuperAdmin();
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

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->role === UserRole::SuperAdmin;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role !== UserRole::Admin) {
            return false;
        }

        if (!is_array($this->permissions) || empty($this->permissions)) {
            return false;
        }

        if (in_array('*', $this->permissions, true) || in_array($permission, $this->permissions, true)) {
            return true;
        }

        if ($permission === 'manage_questions' && (
            in_array('load_questions', $this->permissions, true) ||
            in_array('edit_questions', $this->permissions, true) ||
            in_array('delete_questions', $this->permissions, true)
        )) {
            return true;
        }

        return false;
    }

    public function isUser(): bool
    {
        return $this->role === UserRole::User;
    }

    public function canManageExam(Exam $exam): bool
    {
        return $this->isAdmin();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeUsers(Builder $query): Builder
    {
        return $query->where('role', UserRole::User);
    }
}
