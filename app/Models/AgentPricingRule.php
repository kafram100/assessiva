<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'network_type',
        'package_size',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
