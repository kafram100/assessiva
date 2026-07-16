<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

class HubtelGateway implements PaymentGatewayInterface
{
    public function initiate(array $params): array
    {
        return [
            'success' => false,
            'message' => 'Hubtel gateway not yet configured. Please set up your Hubtel merchant account.',
        ];
    }

    public function verify(string $reference): array
    {
        return [
            'success' => false,
            'message' => 'Hubtel gateway not yet configured.',
        ];
    }

    public function handleCallback(Request $request): array
    {
        return [
            'success' => false,
            'message' => 'Hubtel gateway not yet configured.',
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        return [
            'success' => false,
            'message' => 'Hubtel gateway not yet configured.',
        ];
    }
}
