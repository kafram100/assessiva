<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'agent_order_id',
        'network_type',
        'api_endpoint',
        'request_data',
        'response_data',
        'response_code',
        'success',
        'error_message',
        'execution_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'response_data' => 'array',
            'response_code' => 'integer',
            'success' => 'boolean',
            'execution_time_ms' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function agentOrder()
    {
        return $this->belongsTo(AgentOrder::class, 'agent_order_id');
    }
}
