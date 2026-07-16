<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubAgentBalanceHistory extends Model
{
    use HasFactory;

    protected $table = 'sub_agent_balance_history';

    protected $fillable = [
        'sub_agent_id',
        'change_amount',
        'balance_after',
        'reason',
        'ref_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'change_amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function subAgent()
    {
        return $this->belongsTo(SubAgent::class, 'sub_agent_id');
    }
}
