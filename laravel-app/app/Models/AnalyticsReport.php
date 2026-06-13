<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsReport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_user_id',
        'scope',
        'course_id',
        'course_name',
        'department_name',
        'context_key',
        'cohort_students_snapshot',
        'course_health_score',
        'at_risk_students',
        'weak_topics',
        'performance_clusters',
        'interventions',
        'summary',
        'department_insights',
        'cohort_trends',
        'raw_ai_response',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'cohort_students_snapshot' => 'array',
            'at_risk_students' => 'array',
            'weak_topics' => 'array',
            'performance_clusters' => 'array',
            'interventions' => 'array',
            'summary' => 'array',
            'department_insights' => 'array',
            'cohort_trends' => 'array',
            'raw_ai_response' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
