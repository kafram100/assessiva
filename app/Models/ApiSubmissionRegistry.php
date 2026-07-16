<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSubmissionRegistry extends Model
{
    use HasFactory;

    protected $table = 'api_submission_registry';

    protected $fillable = [
        'order_id',
        'agent_order_id',
        'network_type',
        'reference',
        'external_transaction_id',
        'api_response',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'api_response' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function agentOrder()
    {
        return $this->belongsTo(AgentOrder::class, 'agent_order_id');
    }
}
