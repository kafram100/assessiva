<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheatEvent extends Model
{
    protected $table = 'cheat_events';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attempt_id',
        'event_type',
        'metadata',
        'severity',
        'penalty_marks',
        'penalty_applied',
        'reviewed_by',
        'reviewed_at',
        'review_status',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'penalty_marks' => 'decimal:2',
            'penalty_applied' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @var array<string, string> */
    public static array $severityMap = [
        'tab_switch' => 'high',
        'window_blur' => 'medium',
        'window_focus' => 'low',
        'no_face_detected' => 'high',
        'multiple_faces' => 'critical',
        'looking_away' => 'medium',
        'noise_detected' => 'medium',
        'audio_noise' => 'medium',
        'copy_attempt' => 'medium',
        'paste_attempt' => 'medium',
        'copy_paste' => 'medium',
        'cut_attempt' => 'medium',
        'right_click' => 'low',
        'fullscreen_exit' => 'high',
        'exit_fullscreen' => 'high',
        'screenshot_attempt' => 'high',
        'dev_tools' => 'high',
        'camera_disabled' => 'low',
        'microphone_disabled' => 'medium',
        'audio_detected' => 'medium',
        'face_not_visible' => 'medium',
        'suspicious_movement' => 'high',
        'network_disconnect' => 'medium',
        'print_attempt' => 'medium',
    ];
}
