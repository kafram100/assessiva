<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGatewayInterface
{
    protected string $secretKey;
    protected string $publicKey;

    public function __construct(?string $secretKey = null, ?string $publicKey = null)
    {
        $this->secretKey = $secretKey ?? config('services.paystack.secret');
        $this->publicKey = $publicKey ?? config('services.paystack.public_key');
    }

    public function configure(string $publicKey, string $secretKey): void
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }

    public function initiate(array $params): array
    {
        $fields = [
            'email' => $params['email'] ?? config('app.name') . '@paystack.com',
            'amount' => (int) ($params['amount'] * 100),
            'reference' => $params['reference'],
            'callback_url' => $params['callback_url'] ?? url('/api/payment/callback'),
            'metadata' => $params['metadata'] ?? [],
        ];

        if (!empty($params['channels'])) {
            $fields['channels'] = $params['channels'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', $fields);

        $result = $response->json();

        if (!$response->successful() || !($result['status'] ?? false)) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initialize payment',
            ];
        }

        return [
            'success' => true,
            'authorization_url' => $result['data']['authorization_url'],
            'reference' => $fields['reference'],
            'access_code' => $result['data']['access_code'] ?? null,
            'raw' => $result,
        ];
    }

    public function verify(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        $result = $response->json();

        if (!$response->successful() || !($result['status'] ?? false)) {
            return [
                'success' => false,
                'message' => 'Verification failed',
            ];
        }

        $data = $result['data'];

        return [
            'success' => ($data['status'] ?? '') === 'success',
            'status' => $data['status'] ?? 'unknown',
            'amount' => ($data['amount'] ?? 0) / 100,
            'transaction_id' => $data['id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'channel' => $data['channel'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'raw' => $result,
        ];
    }

    public function handleCallback(Request $request): array
    {
        $reference = $request->query('reference') ?? $request->input('reference');

        if (!$reference) {
            return [
                'success' => false,
                'message' => 'No reference provided',
            ];
        }

        return $this->verify($reference);
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        $fields = [
            'transaction' => $transactionId,
        ];
        if ($amount !== null) {
            $fields['amount'] = (int) ($amount * 100);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/refund', $fields);

        $result = $response->json();

        if (!$response->successful() || !($result['status'] ?? false)) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to process refund on Paystack',
                'raw' => $result,
            ];
        }

        return [
            'success' => true,
            'message' => $result['message'] ?? 'Refund processed successfully',
            'raw' => $result,
        ];
    }
}
