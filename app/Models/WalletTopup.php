<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTopup extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'account_type',
        'account_id',
        'email',
        'base_amount',
        'processing_fee',
        'total_amount',
        'provider',
        'status',
        'credited_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'credited_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function account()
    {
        return $this->morphTo();
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'account_id');
    }
}
