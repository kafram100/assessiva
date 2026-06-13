<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamFairnessReport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'fairness_score',
        'overall_status',
        'estimated_total_duration_minutes',
        'duration_status',
        'difficulty_distribution',
        'blooms_distribution',
        'cognitive_difficulty_map',
        'question_analysis',
        'recommendations',
        'raw_ai_response',
        'questions_fingerprint',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'fairness_score' => 'integer',
            'estimated_total_duration_minutes' => 'integer',
            'difficulty_distribution' => 'array',
            'blooms_distribution' => 'array',
            'cognitive_difficulty_map' => 'array',
            'question_analysis' => 'array',
            'recommendations' => 'array',
            'raw_ai_response' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
