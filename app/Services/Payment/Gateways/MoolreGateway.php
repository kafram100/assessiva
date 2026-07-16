<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MoolreGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $username;
    protected string $apiKey;
    protected string $accountNumber;

    public function __construct()
    {
        $this->baseUrl = config('services.moolre.base_url', 'https://api.moolre.com');
        $this->username = config('services.moolre.username');
        $this->apiKey = config('services.moolre.api_key');
        $this->accountNumber = config('services.moolre.account_number');
    }

    public function initiate(array $params): array
    {
        if (!empty($params['checkout'])) {
            return $this->createCheckoutLink($params);
        }

        $phone = $params['phone'];
        $channel = $this->detectChannel($phone);

        $payload = [
            'type' => 1,
            'channel' => $channel,
            'currency' => 'GHS',
            'payer' => $phone,
            'amount' => (string) $params['amount'],
            'externalref' => $params['reference'],
            'accountnumber' => $this->accountNumber,
        ];

        $response = Http::withHeaders([
            'X-API-USER' => $this->username,
            'X-API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/open/transact/payment", $payload);

        $result = $response->json();

        if (!$response->successful() || ($result['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate Moolre payment',
                'code' => $result['code'] ?? null,
            ];
        }

        $transactionId = $result['data'] ?? null;

        if (!$transactionId || $transactionId === 'all') {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate Moolre payment',
                'code' => $result['code'] ?? null,
            ];
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'provider_message' => $result['message'] ?? null,
            'raw' => $result,
        ];
    }

    protected function createCheckoutLink(array $params): array
    {
        $payload = [
            'type' => 1,
            'amount' => (string) $params['amount'],
            'email' => $params['email'] ?? 'customer@example.com',
            'externalref' => $params['reference'],
            'callback' => $params['callback_url'] ?? url('/api/payment/callback'),
            'redirect' => $params['redirect_url'] ?? url('/agent/wallet'),
            'reusable' => '0',
            'expiration_time' => 30,
            'currency' => 'GHS',
            'accountnumber' => $this->accountNumber,
            'metadata' => $params['metadata'] ?? [],
        ];

        $response = Http::withHeaders([
            'X-API-USER' => $this->username,
            'X-API-PUBKEY' => config('services.moolre.public_key'),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/embed/link", $payload);

        $result = $response->json();

        if (!$response->successful() || ($result['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to create Moolre checkout link',
                'code' => $result['code'] ?? null,
            ];
        }

        return [
            'success' => true,
            'authorization_url' => $result['data']['authorization_url'] ?? null,
            'reference' => $result['data']['reference'] ?? $params['reference'],
            'raw' => $result,
        ];
    }

    public function verify(string $reference): array
    {
        $payload = [
            'type' => 1,
            'idtype' => 1,
            'id' => $reference,
            'accountnumber' => $this->accountNumber,
        ];

        $response = Http::withHeaders([
            'X-API-USER' => $this->username,
            'X-API-PUBKEY' => config('services.moolre.public_key'),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/open/transact/status", $payload);

        $result = $response->json();

        if (!$response->successful() || ($result['status'] ?? 0) !== 1) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to verify payment',
            ];
        }

        $data = $result['data'] ?? [];
        $txstatus = (int) ($data['txstatus'] ?? 0);

        return [
            'success' => $txstatus === 1,
            'status' => match ($txstatus) {
                1 => 'completed',
                2 => 'pending',
                3 => 'failed',
                default => 'unknown',
            },
            'transaction_id' => $data['transactionid'] ?? null,
            'amount' => $data['amount'] ?? null,
            'payer' => $data['payer'] ?? null,
            'external_ref' => $data['externalref'] ?? null,
            'raw' => $result,
        ];
    }

    public function handleCallback(Request $request): array
    {
        $reference = $request->input('externalref') ?? $request->input('reference');

        if (!$reference) {
            return [
                'success' => false,
                'message' => 'No reference provided',
            ];
        }

        return $this->verify($reference);
    }

    protected function detectChannel(?string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        $prefixes = [
            '13' => ['024', '025', '053', '054', '055', '059'],
            '6' => ['020', '050'],
            '7' => ['026', '027', '056', '057'],
        ];

        foreach ($prefixes as $channel => $codes) {
            foreach ($codes as $code) {
                if (str_starts_with($cleaned, $code) || str_starts_with($cleaned, '233' . ltrim($code, '0'))) {
                    return $channel;
                }
            }
        }

        return '13';
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        return [
            'success' => false,
            'message' => 'Refund not supported on Moolre API. Please refund manually via Moolre dashboard.',
        ];
    }
}
