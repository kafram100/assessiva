<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_ref',
        'tracking_id',
        'agent_id',
        'sub_agent_id',
        'customer_phone',
        'customer_name',
        'customer_email',
        'package_id',
        'network_type',
        'package_size_gb',
        'processing_fee',
        'base_cost',
        'base_price',
        'sold_price',
        'agent_profit',
        'sub_agent_profit',
        'your_profit',
        'payment_method',
        'payment_reference',
        'status',
        'failure_code',
        'failure_reason',
        'visibility_scope',
        'payment_status',
        'linked_agent_order_id',
        'external_transaction_id',
        'external_reference',
        'api_response_data',
        'api_response',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'package_size_gb' => 'float',
            'processing_fee' => 'float',
            'base_cost' => 'float',
            'base_price' => 'float',
            'sold_price' => 'float',
            'agent_profit' => 'float',
            'sub_agent_profit' => 'float',
            'your_profit' => 'float',
            'api_response_data' => 'array',
            'api_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function subAgent()
    {
        return $this->belongsTo(SubAgent::class, 'sub_agent_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(StoreOrderStatusHistory::class, 'order_id');
    }

    public function package()
    {
        return $this->belongsTo(AgentPackage::class, 'package_id');
    }
}
