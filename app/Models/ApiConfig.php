<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiConfig extends Model
{
    use HasFactory;

    protected $table = 'api_config';

    protected $fillable = [
        'network_type',
        'api_name',
        'api_endpoint',
        'api_key',
        'api_key_id',
        'api_secret',
        'request_method',
        'request_headers',
        'request_body_template',
        'response_success_field',
        'response_data_field',
        'response_error_field',
        'timeout_seconds',
        'retry_attempts',
        'status_endpoint',
        'balance_endpoint',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body_template' => 'array',
            'timeout_seconds' => 'integer',
            'retry_attempts' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
