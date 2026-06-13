<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheatingIncident extends Model
{
    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'user_id',
        'incident_type',
        'evidence_path',
        'confidence_level',
        'status',
        'incident_timestamp',
        'notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_timestamp' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class, 'exam_attempt_id');
    }
}
