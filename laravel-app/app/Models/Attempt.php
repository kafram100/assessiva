<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attempt extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'student_index',
        'student_fullname',
        'student_level',
        'student_email',
        'start_time',
        'submit_time',
        'score',
        'raw_score',
        'proctoring_penalty_total',
        'total_marks',
        'status',
        'notes',
        'locked_by_session',
        'exam_device_key',
        'question_order',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'submit_time' => 'datetime',
            'score' => 'decimal:2',
            'raw_score' => 'decimal:2',
            'proctoring_penalty_total' => 'decimal:2',
            'question_order' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function cheatEvents(): HasMany
    {
        return $this->hasMany(CheatEvent::class);
    }

    public function cheatingIncidents(): HasMany
    {
        return $this->hasMany(CheatingIncident::class, 'exam_attempt_id');
    }

    public function revisionPlan(): HasOne
    {
        return $this->hasOne(RevisionPlan::class, 'exam_attempt_id');
    }
}
