<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPracticeSession extends Model
{
    protected $fillable = [
        'user_id',
        'topic',
        'questions',
        'responses',
        'score',
        'ai_feedback',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'responses' => 'array',
            'ai_feedback' => 'array',
            'score' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
