<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningTwin extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lecturer_user_id',
        'student_index',
        'course_id',
        'context_key',
        'source_attempt_id',
        'input_snapshot',
        'academic_health_score',
        'risk_level',
        'predicted_next_score',
        'strengths',
        'weaknesses',
        'learning_pattern',
        'recommended_actions',
        'revision_focus',
        'lecturer_intervention',
        'raw_ai_response',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
            'recommended_actions' => 'array',
            'revision_focus' => 'array',
            'raw_ai_response' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
