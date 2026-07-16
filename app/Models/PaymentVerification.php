<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentVerification extends Model
{
    protected $fillable = [
        'reference',
        'gateway',
        'user_type',
        'user_id',
        'amount',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'verified_at' => 'datetime',
        ];
    }
}
