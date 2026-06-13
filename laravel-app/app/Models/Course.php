<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'department_id',
        'code',
        'name',
        'description',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allowedIndexes(): HasMany
    {
        return $this->hasMany(CourseAllowedIndex::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
