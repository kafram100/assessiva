<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeQuizAttempt extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'quiz_id',
        'student_name',
        'student_email',
        'student_index',
        'department',
        'score',
        'total_marks',
        'responses',
        'started_at',
        'submitted_at',
        'duration_seconds',
        'weak_topics',
        'ai_feedback',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'responses' => 'array',
            'weak_topics' => 'array',
            'ai_feedback' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'quiz_id');
    }
}
