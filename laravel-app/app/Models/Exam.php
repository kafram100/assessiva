<?php

namespace App\Models;

use App\Enums\PracticeVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exam extends Model
{
    /**
     * @var list<string>
     */
    protected $appends = [
        'is_proctoring_required',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'course_name',
        'course_code',
        'instructions',
        'duration',
        'attempts_allowed',
        'start_datetime',
        'end_datetime',
        'is_published',
        'results_released',
        'show_student_ai_feedback',
        'show_student_expected_answers',
        'show_student_revision_suggestions',
        'exam_code',
        'exam_type',
        'proctoring_mode',
        'shuffle_questions',
        'submission_password_enabled',
        'submission_password',
        'assessment_file',
        'is_public_practice',
        'practice_mode',
        'allow_retry',
        'show_answers_after_submit',
        'show_explanations',
        'practice_duration_minutes',
        'target_school_id',
        'target_department_id',
        'target_program',
        'target_level',
        'visibility_status',
        'difficulty_level',
        'ai_recommended',
        'practice_topic',
        'is_featured_practice',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'submission_password',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'results_released' => 'boolean',
            'show_student_ai_feedback' => 'boolean',
            'show_student_expected_answers' => 'boolean',
            'show_student_revision_suggestions' => 'boolean',
            'shuffle_questions' => 'boolean',
            'submission_password_enabled' => 'boolean',
            'is_public_practice' => 'boolean',
            'practice_mode' => 'boolean',
            'allow_retry' => 'boolean',
            'show_answers_after_submit' => 'boolean',
            'show_explanations' => 'boolean',
            'ai_recommended' => 'boolean',
            'is_featured_practice' => 'boolean',
            'visibility_status' => PracticeVisibility::class,
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('id');
    }

    /**
     * @return HasOne<ExamFairnessReport, $this>
     */
    public function fairnessReport(): HasOne
    {
        return $this->hasOne(ExamFairnessReport::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    /**
     * @return HasMany<RevisionPlan, $this>
     */
    public function revisionPlans(): HasMany
    {
        return $this->hasMany(RevisionPlan::class);
    }

    public function allowedIndexes(): HasMany
    {
        return $this->hasMany(ExamAllowedIndex::class);
    }

    public function cheatingPenalties(): HasMany
    {
        return $this->hasMany(ExamCheatingPenalty::class);
    }

    /**
     * @return HasMany<ProctoringPenaltyRule, $this>
     */
    public function proctoringPenaltyRules(): HasMany
    {
        return $this->hasMany(ProctoringPenaltyRule::class);
    }

    /**
     * @return HasMany<PracticeQuizAttempt, $this>
     */
    public function practiceQuizAttempts(): HasMany
    {
        return $this->hasMany(PracticeQuizAttempt::class, 'quiz_id');
    }

    public function targetSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'target_school_id');
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isStudentIndexAllowed(string $studentIndex): bool
    {
        $normalized = ExamAllowedIndex::normalize($studentIndex);
        if ($normalized === '') {
            return false;
        }

        return $this->allowedIndexes()->where('student_index', $normalized)->exists();
    }

    public function hasAllowedIndexWhitelist(): bool
    {
        return $this->allowedIndexes()->exists();
    }

    public function requiresProctoring(): bool
    {
        if ($this->practice_mode) {
            return false;
        }

        $mode = strtolower(trim((string) ($this->proctoring_mode ?? 'auto')));
        if (in_array($mode, ['enabled', 'on', 'required'], true)) {
            return true;
        }
        if (in_array($mode, ['disabled', 'off', 'none'], true)) {
            return false;
        }

        $type = strtolower(trim($this->exam_type ?? ''));

        return in_array($type, ['exam', 'mid-semester', 'mid semester', 'quiz'], true);
    }

    public function getIsProctoringRequiredAttribute(): bool
    {
        return $this->requiresProctoring();
    }

    public function scopePracticeBrowseable(Builder $query): Builder
    {
        return $query
            ->where('practice_mode', true)
            ->where('is_public_practice', true)
            ->where('is_published', true);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopePracticeFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['school_id'])) {
            $sid = (int) $filters['school_id'];
            $query->where(function (Builder $q) use ($sid): void {
                $q->whereNull('target_school_id')
                    ->orWhere('target_school_id', $sid);
            });
        }
        if (! empty($filters['department_id'])) {
            $query->where(function (Builder $q) use ($filters): void {
                $q->whereNull('target_department_id')
                    ->orWhere('target_department_id', (int) $filters['department_id']);
            });
        }
        if (! empty($filters['program'])) {
            $program = (string) $filters['program'];
            $query->where(function (Builder $q) use ($program): void {
                $q->whereNull('target_program')
                    ->orWhere('target_program', $program);
            });
        }
        if (! empty($filters['level'])) {
            $level = (string) $filters['level'];
            $query->where(function (Builder $q) use ($level): void {
                $q->whereNull('target_level')
                    ->orWhere('target_level', $level);
            });
        }
        if (! empty($filters['course'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['course']).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('course_name', 'like', $term)
                    ->orWhere('course_code', 'like', $term);
            });
        }
        if (! empty($filters['topic'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['topic']).'%';
            $query->where('practice_topic', 'like', $term);
        }
        if (! empty($filters['lecturer_id'])) {
            $query->where('user_id', (int) $filters['lecturer_id']);
        }
        if (! empty($filters['difficulty'])) {
            $d = (string) $filters['difficulty'];
            $query->where(function (Builder $q) use ($d): void {
                $q->whereNull('difficulty_level')
                    ->orWhere('difficulty_level', '')
                    ->orWhere('difficulty_level', $d);
            });
        }
        if (! empty($filters['search'])) {
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']).'%';
            $query->where(function (Builder $q) use ($s): void {
                $q->where('title', 'like', $s)
                    ->orWhere('course_name', 'like', $s)
                    ->orWhere('course_code', 'like', $s)
                    ->orWhere('practice_topic', 'like', $s)
                    ->orWhere('exam_code', 'like', $s);
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopePracticeVisibilityForFilters(Builder $query, array $filters): Builder
    {
        $deptId = isset($filters['department_id']) ? (int) $filters['department_id'] : null;
        $program = isset($filters['program']) ? (string) $filters['program'] : null;

        return $query->where(function (Builder $q) use ($deptId, $program): void {
            $q->where('visibility_status', PracticeVisibility::Public)
                ->orWhere(function (Builder $q2) use ($deptId): void {
                    if ($deptId) {
                        $q2->where('visibility_status', PracticeVisibility::DepartmentOnly)
                            ->where('target_department_id', $deptId);
                    } else {
                        $q2->whereRaw('1 = 0');
                    }
                })
                ->orWhere(function (Builder $q3) use ($program): void {
                    if ($program !== null && $program !== '') {
                        $q3->where('visibility_status', PracticeVisibility::ProgramOnly)
                            ->where('target_program', $program);
                    } else {
                        $q3->whereRaw('1 = 0');
                    }
                });
        });
    }
}
