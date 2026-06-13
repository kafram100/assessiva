<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSecurityLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'user_id',
        'activity_type',
        'description',
        'timestamp',
        'ip_address',
        'severity',
        'reviewed',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'reviewed' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class, 'exam_attempt_id');
    }
}
