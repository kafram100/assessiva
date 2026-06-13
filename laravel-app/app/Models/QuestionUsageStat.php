<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionUsageStat extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_bank_id',
        'times_used',
        'average_score',
        'correct_percentage',
        'difficulty_performance',
        'common_wrong_answers',
        'ai_grading_consistency',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'common_wrong_answers' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
}
