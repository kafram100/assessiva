<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionPlan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'exam_attempt_id',
        'student_index',
        'student_name',
        'student_email',
        'course_name',
        'overall_score',
        'risk_level',
        'weak_topics',
        'failed_questions',
        'summary',
        'revision_notes',
        'flashcards',
        'practice_questions',
        'study_plan',
        'recommended_actions',
        'ai_provider',
        'raw_ai_response',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'decimal:2',
            'weak_topics' => 'array',
            'failed_questions' => 'array',
            'revision_notes' => 'array',
            'flashcards' => 'array',
            'practice_questions' => 'array',
            'study_plan' => 'array',
            'recommended_actions' => 'array',
            'raw_ai_response' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class, 'exam_attempt_id');
    }
}
