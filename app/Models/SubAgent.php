<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'username',
        'email',
        'ref_code',
        'balance',
        'preferred_gateway',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function storeOrders()
    {
        return $this->hasMany(StoreOrder::class, 'sub_agent_id');
    }

    public function balanceHistory()
    {
        return $this->hasMany(SubAgentBalanceHistory::class, 'sub_agent_id');
    }
}
