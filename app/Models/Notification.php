<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'recipient_type',
        'recipient_id',
        'sender_id',
        'expires_at',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_read' => 'boolean',
        ];
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
