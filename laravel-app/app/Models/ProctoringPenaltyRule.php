<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProctoringPenaltyRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'event_type',
        'penalty_marks',
        'is_enabled',
        'severity',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'penalty_marks' => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
