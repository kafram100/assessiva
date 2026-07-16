<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'external_transaction_id',
        'webhook_type',
        'payload',
        'response_status',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
            'processed' => 'boolean',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
