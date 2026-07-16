<?php

namespace App\Services\Payment\Contracts;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function initiate(array $params): array;
    public function verify(string $reference): array;
    public function handleCallback(Request $request): array;
    public function refund(string $transactionId, ?float $amount = null): array;
}
