<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_size',
        'package_name',
        'amount',
        'base_cost',
        'processing_fee',
        'beneficiary_number',
        'network_type',
        'status',
        'order_reference',
        'reference',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function apiSubmissionRegistry()
    {
        return $this->hasOne(ApiSubmissionRegistry::class, 'order_id');
    }
}
