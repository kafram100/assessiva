<?php

namespace App\Services\Payment;

use App\Models\AgentStoreSetting;
use App\Models\AppSetting;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\HubtelGateway;
use App\Services\Payment\Gateways\MoolreGateway;
use App\Services\Payment\Gateways\PaystackGateway;

class PaymentRouter
{
    const CONTEXT_WALLET_TOPUP = 'wallet_topup';
    const CONTEXT_PUBLIC_ORDER = 'public_order';
    const CONTEXT_STORE_ORDER = 'store_order';

    protected array $gateways = [];

    public function __construct()
    {
        $this->gateways = [
            'moolre' => app(MoolreGateway::class),
            'paystack' => app(PaystackGateway::class),
            'hubtel' => app(HubtelGateway::class),
        ];
    }

    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?? 'paystack';

        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Payment gateway '{$name}' not found.");
        }

        return $this->gateways[$name];
    }

    public function forContext(string $context, ?int $agentId = null): PaymentGatewayInterface
    {
        return match ($context) {
            self::CONTEXT_WALLET_TOPUP => $this->gateway(
                AppSetting::getValue('default_wallet_gateway', 'moolre')
            ),
            self::CONTEXT_PUBLIC_ORDER => $this->gateway(
                AppSetting::getValue('default_public_gateway', 'paystack')
            ),
            self::CONTEXT_STORE_ORDER => $this->gateway($this->getStoreGateway($agentId)),
            default => $this->gateway('paystack'),
        };
    }

    protected function getStoreGateway(?int $agentId): string
    {
        if (!$agentId) {
            return 'paystack';
        }

        $setting = AgentStoreSetting::where('agent_id', $agentId)->first();

        return $setting?->preferred_gateway ?? 'paystack';
    }

    public function forDealerStore(int $agentId): PaymentGatewayInterface
    {
        $gatewayName = $this->getStoreGateway($agentId);
        $gateway = $this->gateway($gatewayName);

        if ($gateway instanceof PaystackGateway) {
            $setting = AgentStoreSetting::where('agent_id', $agentId)->first();
            if ($setting && $setting->paystack_public_key && $setting->paystack_secret_key) {
                $gateway->configure($setting->paystack_public_key, $setting->paystack_secret_key);
            }
        }

        return $gateway;
    }

    public function availableForContext(string $context): array
    {
        return match ($context) {
            self::CONTEXT_WALLET_TOPUP => ['moolre', 'paystack'],
            self::CONTEXT_PUBLIC_ORDER => ['paystack', 'moolre'],
            self::CONTEXT_STORE_ORDER => ['moolre', 'paystack', 'hubtel'],
            default => ['paystack'],
        };
    }
}
