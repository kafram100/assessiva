<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceHistory extends Model
{
    use HasFactory;

    protected $table = 'balance_history';

    protected $fillable = [
        'agent_id',
        'change_amount',
        'balance_after',
        'reason',
        'ref_id',
        'beneficiary_number',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'change_amount' => 'float',
            'balance_after' => 'float',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
