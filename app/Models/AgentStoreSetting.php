<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentStoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'store_name',
        'store_slug',
        'store_description',
        'is_active',
        'theme_color',
        'accent_color',
        'logo_url',
        'contact_phone',
        'contact_whatsapp',
        'preferred_gateway',
        'paystack_public_key',
        'paystack_secret_key',
        'hero_headline',
        'announcement_text',
        'footer_tagline',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'paystack_secret_key' => 'encrypted',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
