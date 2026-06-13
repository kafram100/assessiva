<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamActivityLog extends Model
{
    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'user_id',
        'activity_type',
        'description',
        'severity',
        'timestamp',
        'ip_address',
        'user_agent',
        'additional_data',
        'reviewed',
        'reviewed_by',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'additional_data' => 'array',
            'reviewed' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class, 'exam_attempt_id');
    }
}
