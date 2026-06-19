<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuestionBank extends Model
{
    protected $table = 'question_bank';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'level',
        'lecturer_id',
        'department_id',
        'topic_id',
        'topic',
        'question_text',
        'question_type',
        'difficulty',
        'correct_answer',
        'model_answer',
        'marking_scheme',
        'suggested_marks',
        'generated_by_ai',
        'is_active',
        'ai_confidence',
        'source_reference',
        'created_by',
        'version',
        'parent_question_id',
        'ai_generation_request_id',
    ];

    protected function casts(): array
    {
        return [
            'generated_by_ai' => 'boolean',
            'is_active' => 'boolean',
            'ai_confidence' => 'decimal:2',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_bank_id')->orderBy('option_key');
    }

    /**
     * @return HasMany<QuestionVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'question_bank_id')->orderByDesc('version');
    }

    public function usageStats(): HasOne
    {
        return $this->hasOne(QuestionUsageStat::class, 'question_bank_id');
    }

    public function aiGenerationRequest(): BelongsTo
    {
        return $this->belongsTo(AiGenerationRequest::class, 'ai_generation_request_id');
    }

    public function parentQuestion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_question_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'question_bank_id');
    }

    /**
     * @param  Builder<QuestionBank>  $query
     * @return Builder<QuestionBank>
     */
    public function scopeVisibleToLecturer(Builder $query, User $user): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($user): void {
                $q->where('lecturer_id', $user->id)
                  ->orWhere(function (Builder $official) use ($user): void {
                      $official->whereNull('department_id');
                      if ($user->department_id) {
                          $official->orWhere('department_id', $user->department_id);
                      }
                  });
            });
    }
}
