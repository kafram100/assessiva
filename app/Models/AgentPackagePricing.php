<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentPackagePricing extends Model
{
    use HasFactory;

    protected $table = 'agent_package_pricing';

    protected $fillable = [
        'agent_id',
        'package_id',
        'customer_price',
        'sub_agent_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'customer_price' => 'decimal:2',
            'sub_agent_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function agentPackage()
    {
        return $this->belongsTo(AgentPackage::class, 'package_id');
    }
}
