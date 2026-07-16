<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsConfig extends Model
{
    use HasFactory;

    protected $table = 'sms_config';

    protected $fillable = [
        'config_key',
        'config_value',
        'api_url',
        'api_key',
        'sender_name',
        'callback_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
