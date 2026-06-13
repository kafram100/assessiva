<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionProctoring extends Model
{
    protected $table = 'exam_sessions_proctoring';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'student_id',
        'lecturer_id',
        'video_recording_path',
        'audio_recording_path',
        'start_time',
        'end_time',
        'suspicious_activity_count',
        'proctoring_status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class, 'exam_attempt_id');
    }
}
