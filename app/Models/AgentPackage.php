<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'network_type',
        'package_name',
        'package_size_gb',
        'package_size_mb',
        'price',
        'sort_order',
        'is_active',
        'manual_fulfillment',
        'voice_minutes',
        'non_expiring',
    ];

    protected function casts(): array
    {
        return [
            'package_size_gb' => 'float',
            'package_size_mb' => 'integer',
            'price' => 'float',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'manual_fulfillment' => 'boolean',
            'voice_minutes' => 'integer',
            'non_expiring' => 'boolean',
        ];
    }

    public function agentPackagePricing()
    {
        return $this->hasMany(AgentPackagePricing::class, 'package_id');
    }

    public function storeOrders()
    {
        return $this->hasMany(StoreOrder::class, 'package_id');
    }
}
