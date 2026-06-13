<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamCheatingPenalty extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'event_type',
        'is_enabled',
        'deduction_points',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'deduction_points' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
