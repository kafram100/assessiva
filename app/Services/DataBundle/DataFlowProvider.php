<?php

namespace App\Services\DataBundle;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataFlowProvider
{
    protected string $baseUrl;
    protected string $email;
    protected string $password;
    protected ?string $apiToken;

    protected const NETWORK_MAP = [
        'mtn' => 5,
        'telecel' => 6,
        'vodafone' => 6,
        'voda' => 6,
        'airtel' => 7,
        'tigo' => 7,
        'atishare' => 7,
        'atbigtime' => 8,
        'bigtime' => 8,
    ];

    protected const NETWORK_REVERSE = [
        5 => 'MTN',
        6 => 'TELECEL',
        7 => 'ISHARE',
        8 => 'BIGTIME',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.dataflow.base_url', 'https://dataflowghana.com/api/v1'), '/');
        $this->email = config('services.dataflow.email') ?? '';
        $this->password = config('services.dataflow.password') ?? '';
        $this->apiToken = config('services.dataflow.api_token');
    }

    public function getToken(): ?string
    {
        if (!empty($this->apiToken)) {
            return $this->apiToken;
        }

        return Cache::remember('dataflow_token', 86400, function () {
            return $this->login();
        });
    }

    public function login(): ?string
    {
        if (empty($this->email) || empty($this->password)) {
            Log::warning('DataFlow: email or password not configured');
            return null;
        }

        try {
            $response = Http::timeout(15)->post($this->baseUrl . '/token/create', [
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $body = $response->json() ?? [];

            return $body['token'] ?? $body['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('DataFlow login failed: ' . $e->getMessage());
            return null;
        }
    }

    public function purchaseData(
        string $msisdn,
        string $network,
        int $capacityMb,
        string $paymentMethod = 'wallet',
        ?int $localOrderId = null,
        ?string $submissionKey = null,
    ): array {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate', 'data' => null];
        }

        $networkId = $this->resolveNetworkId($network);
        $sizeLabel = $this->formatSize($capacityMb);

        $payload = [
            'beneficiary_number' => $msisdn,
            'network_id' => $networkId,
            'size' => $sizeLabel,
        ];

        $startTime = microtime(true);
        $response = $this->sendPost('/normal-orders', $payload, $token);
        $executionTime = (int) round((microtime(true) - $startTime) * 1000);

        $this->logApiCall($localOrderId, $network, $payload, $response, $executionTime);

        return $this->normalizeResponse($response);
    }

    public function fetchBalance(): array
    {
        return [
            'success' => true,
            'balance' => null,
            'data' => [],
            'http_code' => 200,
            'error' => null,
            'note' => 'DataFlow does not expose a wallet balance endpoint',
        ];
    }

    public function getOrders(): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate'];
        }

        return $this->sendGet('/normal-orders', $token);
    }

    public function getTransactions(): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate'];
        }

        return $this->sendGet('/transactions', $token);
    }

    public function getTransaction(string $id): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate'];
        }

        return $this->sendGet("/transactions/{$id}", $token);
    }

    public function checkOrderStatus(string $externalId): array
    {
        $result = $this->getTransaction($externalId);
        $data = $result['data'] ?? [];
        $status = $data['status'] ?? $data['order_status'] ?? '';

        $result['is_delivered'] = in_array(strtolower((string) $status), ['delivered', 'completed', 'success', 'fulfilled', 'done']);
        $result['is_failed'] = in_array(strtolower((string) $status), ['failed', 'cancelled', 'rejected', 'error', 'refunded']);

        return $result;
    }

    public function getTransactionStatus(): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate'];
        }

        return $this->sendGet('/transaction-status', $token);
    }

    public function getAfaProducts(): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate'];
        }

        return $this->sendGet('/afa/products', $token);
    }

    public function createAfaOrder(array $data): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'DataFlow: unable to authenticate'];
        }

        return $this->sendPost('/afa', $data, $token);
    }

    protected function sendPost(string $path, array $payload, string $token): array
    {
        $url = $this->baseUrl . $path;

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $httpCode = $response->status();
            $body = $response->json() ?? [];

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'data' => $body,
                'http_code' => $httpCode,
                'raw_response' => $response->body(),
                'error' => $body['message'] ?? ($httpCode >= 200 && $httpCode < 300 ? null : 'API call failed'),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => [],
                'http_code' => 0,
                'raw_response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function sendGet(string $path, string $token): array
    {
        $url = $this->baseUrl . $path;

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $httpCode = $response->status();
            $body = $response->json() ?? [];

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'data' => $body,
                'http_code' => $httpCode,
                'raw_response' => $response->body(),
                'error' => $body['message'] ?? ($httpCode >= 200 && $httpCode < 300 ? null : 'API call failed'),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => [],
                'http_code' => 0,
                'raw_response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function resolveNetworkId(string $network): int
    {
        $normalized = strtolower(trim($network));

        foreach (self::NETWORK_MAP as $key => $id) {
            if (str_contains($normalized, $key)) {
                return $id;
            }
        }

        return 5;
    }

    protected function formatSize(int $capacityMb): string
    {
        if ($capacityMb >= 1024 && $capacityMb % 1024 === 0) {
            return ($capacityMb / 1024) . 'GB';
        }

        if ($capacityMb >= 1000) {
            return round($capacityMb / 1024, 1) . 'GB';
        }

        return $capacityMb . 'MB';
    }

    protected function normalizeResponse(array $response): array
    {
        $data = $response['data'] ?? [];
        $order = $data['order'] ?? [];

        $externalTransactionId = $order['reference_id'] ?? $data['reference_id'] ?? null;
        $externalReference = $order['reference_id'] ?? $data['reference_id'] ?? null;
        $price = $order['amount'] ?? $data['amount'] ?? $order['price'] ?? $data['price'] ?? null;

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
            'api_endpoint' => $this->baseUrl . '/normal-orders',
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
        $normalized = strtolower(trim($network));
        if (str_contains($normalized, 'mtn')) return 'mtn';
        if (str_contains($normalized, 'telecel') || str_contains($normalized, 'vodafone') || str_contains($normalized, 'voda')) return 'telecel';
        if (str_contains($normalized, 'airtel') || str_contains($normalized, 'tigo')) return 'airtel_tigo';
        if (str_contains($normalized, 'bigtime')) return 'bigtime';
        return $normalized;
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
