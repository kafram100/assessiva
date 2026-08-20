<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks which questions each user has seen (completed) in practice sessions.
 * Used to ensure unseen questions are always prioritised, and repeated questions
 * cycle in a fair least-recently-seen order.
 */
class UserSeenQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'question_id',
        'seen_count',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id'      => 'integer',
            'question_id'  => 'integer',
            'seen_count'   => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }
}
