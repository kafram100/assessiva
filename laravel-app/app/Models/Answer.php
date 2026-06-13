<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option',
        'theory_answer',
        'file_upload',
        'is_correct',
        'marks_awarded',
        'ai_marks_awarded',
        'ai_confidence',
        'ai_concept_coverage',
        'ai_evaluation',
        'detected_concepts',
        'missing_concepts',
        'rubric_concepts',
        'evaluation_reason',
        'evaluation_status',
        'expected_answer_snapshot',
        'lecturer_grade_comment',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'marks_awarded' => 'decimal:2',
            'ai_marks_awarded' => 'decimal:2',
            'ai_confidence' => 'decimal:4',
            'ai_concept_coverage' => 'decimal:4',
            'detected_concepts' => 'array',
            'missing_concepts' => 'array',
            'rubric_concepts' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
