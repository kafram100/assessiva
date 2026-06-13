<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionReport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lecturer_user_id',
        'student_index',
        'course_id',
        'student_data_snapshot',
        'failure_probability',
        'dropout_risk',
        'burnout_risk',
        'predicted_grade',
        'intervention_urgency',
        'recommended_interventions',
        'advisory_rationale',
        'raw_ai_response',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'student_data_snapshot' => 'array',
            'recommended_interventions' => 'array',
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
