<?php

namespace App\Services\DataBundle;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;

class GHDataConnectProvider
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ghdataconnect.base_url', 'https://ghdataconnect.com/api'), '/');
        $this->apiKey = config('services.ghdataconnect.api_key', '');
    }

    public function purchaseData(
        string $msisdn,
        string $network,
        int $capacityMb,
        string $paymentMethod = 'wallet',
        ?int $localOrderId = null,
        ?string $submissionKey = null,
    ): array {
        $networkKey = $this->resolveNetworkKey($network);
        $reference = $submissionKey ?? $this->generateReference();

        $payload = [
            'network' => $networkKey,
            'reference' => $reference,
            'msisdn' => $msisdn,
            'capacity' => $capacityMb >= 1024 ? round($capacityMb / 1024) : $capacityMb,
        ];

        $startTime = microtime(true);
        $response = $this->sendPost('/v1/purchaseBundle', $payload);
        $executionTime = (int) round((microtime(true) - $startTime) * 1000);

        $this->logApiCall($localOrderId, $network, $payload, $response, $executionTime);

        return $this->normalizeResponse($response, $reference);
    }

    public function createIshareBundleOrder(
        string $msisdn,
        int $capacityMb,
        ?string $reference = null,
    ): array {
        $reference = $reference ?? $this->generateReference();

        $payload = [
            'reference' => $reference,
            'msisdn' => $msisdn,
            'capacity' => $capacityMb,
        ];

        $startTime = microtime(true);
        $response = $this->sendPost('/v1/createIshareBundleOrder', $payload);
        $executionTime = (int) round((microtime(true) - $startTime) * 1000);

        $this->logApiCall(null, 'atishare', $payload, $response, $executionTime);

        return $this->normalizeResponse($response, $reference);
    }

    public function fetchBalance(): array
    {
        $startTime = microtime(true);
        $response = $this->sendGet('/v1/getWalletBalance');
        $executionTime = (int) round((microtime(true) - $startTime) * 1000);

        $data = $response['data'] ?? [];
        $balance = $data['balance'] ?? null;

        return [
            'success' => $response['success'] ?? false,
            'balance' => $balance,
            'data' => $data,
            'http_code' => $response['http_code'] ?? 0,
            'error' => $response['error'] ?? null,
            'execution_time_ms' => $executionTime,
        ];
    }

    public function checkOrderStatus(string $reference): array
    {
        $response = $this->sendGet("/v1/checkOrderStatus/{$reference}");
        $data = $response['data'] ?? [];
        $status = $data['status'] ?? $data['order_status'] ?? '';

        $response['is_delivered'] = in_array(strtolower((string) $status), ['delivered', 'completed', 'success', 'fulfilled', 'done']);
        $response['is_failed'] = in_array(strtolower((string) $status), ['failed', 'cancelled', 'rejected', 'error', 'refunded']);

        return $response;
    }

    public function getAllNetworks(): array
    {
        return $this->sendGet('/v1/getAllNetworks');
    }

    protected function sendPost(string $path, array $payload): array
    {
        $url = $this->baseUrl . $path;

        try {
            $http = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]);

            $response = $http->post($url, $payload);
            $httpCode = $response->status();
            $body = $response->json() ?? [];

            return [
                'success' => !empty($body['success']),
                'data' => $body['data'] ?? [],
                'message' => $body['message'] ?? null,
                'http_code' => $httpCode,
                'raw_response' => $response->body(),
                'error' => empty($body['success']) ? ($body['message'] ?? 'API call failed') : null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => null,
                'http_code' => 0,
                'raw_response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function sendGet(string $path): array
    {
        $url = $this->baseUrl . $path;

        try {
            $http = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]);

            $response = $http->get($url);
            $httpCode = $response->status();
            $body = $response->json() ?? [];

            return [
                'success' => !empty($body['success']),
                'data' => $body['data'] ?? [],
                'message' => $body['message'] ?? null,
                'http_code' => $httpCode,
                'raw_response' => $response->body(),
                'error' => empty($body['success']) ? ($body['message'] ?? 'API call failed') : null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => null,
                'http_code' => 0,
                'raw_response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function resolveNetworkKey(string $network): string
    {
        $normalized = strtolower(trim($network));

        if (str_contains($normalized, 'mtn')) return 'mtn';
        if (str_contains($normalized, 'telecel') || str_contains($normalized, 'vodafone') || str_contains($normalized, 'voda')) return 'telecel';
        if (str_contains($normalized, 'airtel') || str_contains($normalized, 'tigo')) return 'airteltigo';

        return $normalized;
    }

    protected function generateReference(): string
    {
        return time() . random_int(1000000, 9999999);
    }

    protected function normalizeResponse(array $response, string $reference): array
    {
        $data = $response['data'] ?? [];

        $externalTransactionId = $data['reference'] ?? $data['order_id'] ?? $data['transaction_id'] ?? $reference;
        $externalReference = $data['reference'] ?? $data['transactionReference'] ?? $reference;
        $price = $data['price'] ?? $data['amount'] ?? $data['cost'] ?? null;

        return [
            'success' => $response['success'] ?? false,
            'data' => $data,
            'http_code' => $response['http_code'] ?? 0,
            'raw_response' => $response['raw_response'] ?? null,
            'error' => $response['error'] ?? null,
            'external_transaction_id' => $externalTransactionId,
            'external_reference' => $externalReference,
            'base_cost' => $price !== null ? (float) $price : null,
        ];
    }

    protected function logApiCall(?int $orderId, string $network, ?array $requestData, array $response, int $executionTime): void
    {
        if (!$orderId) return;

        ApiLog::create([
            'order_id' => $orderId,
            'network_type' => $network,
            'api_endpoint' => $this->baseUrl . '/v1/purchaseBundle',
            'request_data' => $requestData,
            'response_data' => [
                'body' => json_decode($response['raw_response'] ?? '{}', true),
                'parsed' => $response['data'],
            ],
            'response_code' => $response['http_code'] ?? 0,
            'success' => $response['success'] ?? false,
            'error_message' => $response['error'] ?? null,
            'execution_time_ms' => $executionTime,
        ]);
    }

    public function normalizeNetwork(string $network): string
    {
        return $this->resolveNetworkKey($network);
    }

    public function validatePhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        return strlen($digits) >= 9 ? $digits : null;
    }

    public function convertPackageSize(string $bundleLabel): int
    {
        $bundleLabel = trim($bundleLabel);

        if (preg_match('/(\d+(?:\.\d+)?)\s*GB/i', $bundleLabel, $matches)) {
            return (int) round((float) $matches[1] * 1024);
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*MB/i', $bundleLabel, $matches)) {
            return (int) round((float) $matches[1]);
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', $bundleLabel, $matches)) {
            $number = (float) $matches[1];
            return $number < 100
                ? (int) round($number * 1024)
                : (int) round($number);
        }

        return 0;
    }
}
