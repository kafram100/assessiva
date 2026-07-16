<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'super_admin']) && $this->status === 'active';
    }

    public function getFilamentName(): string
    {
        return $this->full_name ?? $this->username;
    }

    protected $fillable = [
        'username',
        'email',
        'full_name',
        'name',
        'phone',
        'password',
        'balance',
        'role',
        'status',
        'is_store_enabled',
        'store_commission_rate',
        'minimum_markup_percent',
        'reset_token',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'float',
            'store_commission_rate' => 'decimal:2',
            'minimum_markup_percent' => 'decimal:2',
            'is_store_enabled' => 'boolean',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function agentOrders()
    {
        return $this->hasMany(AgentOrder::class, 'agent_id');
    }

    public function pricingRules()
    {
        return $this->hasMany(AgentPricingRule::class, 'agent_id');
    }

    public function balanceHistory()
    {
        return $this->hasMany(BalanceHistory::class, 'agent_id');
    }

    public function storeSettings()
    {
        return $this->hasOne(AgentStoreSetting::class, 'agent_id');
    }

    public function subAgents()
    {
        return $this->hasMany(SubAgent::class, 'agent_id');
    }

    public function storeOrders()
    {
        return $this->hasMany(StoreOrder::class, 'agent_id');
    }

    public function packagePricing()
    {
        return $this->hasMany(AgentPackagePricing::class, 'agent_id');
    }

    public function walletTopups()
    {
        return $this->hasMany(WalletTopup::class, 'account_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
    }
}
