<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGenerationRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lecturer_id',
        'course_id',
        'topic',
        'input_type',
        'source_file_path',
        'source_content',
        'requested_question_count',
        'question_types',
        'difficulty',
        'total_marks',
        'learning_outcome',
        'prompt_injection_detected',
        'status',
        'ai_provider',
        'ai_model',
        'generated_count',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'question_types' => 'array',
            'prompt_injection_detected' => 'boolean',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * @return HasMany<QuestionBank, $this>
     */
    public function generatedQuestions(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'ai_generation_request_id');
    }
}
