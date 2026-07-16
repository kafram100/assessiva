<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'package_id',
        'package_size',
        'amount',
        'base_cost',
        'beneficiary_number',
        'network_type',
        'status',
        'external_transaction_id',
        'external_reference',
        'api_response_data',
        'momo_name',
        'momo_number',
        'payment_method',
        'tracking_key',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'base_cost' => 'decimal:2',
            'api_response_data' => 'array',
            'status_updated_at' => 'datetime',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function package()
    {
        return $this->belongsTo(AgentPackage::class, 'package_id');
    }

    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class, 'agent_order_id');
    }

    public function apiSubmissionRegistry()
    {
        return $this->hasOne(ApiSubmissionRegistry::class, 'agent_order_id');
    }
}
