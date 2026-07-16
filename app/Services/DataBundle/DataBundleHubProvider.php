<?php

namespace App\Services\DataBundle;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;

class DataBundleHubProvider
{
    protected string $baseUrl;
    protected string $apiKey;

    protected const NETWORK_MAP = [
        'mtn' => 'YELLO',
        'yello' => 'YELLO',
        'telecel' => 'TELECEL',
        'vodafone' => 'TELECEL',
        'voda' => 'TELECEL',
        'airtel' => 'AIRTELTIGO',
        'tigo' => 'AIRTELTIGO',
        'airtel_tigo' => 'AIRTELTIGO',
        'atishare' => 'AIRTELTIGO',
    ];

    protected const NETWORK_PREFIXES = [
        '024', '025', '053', '054', '055', '059' => 'YELLO',
        '020', '050' => 'TELECEL',
        '026', '027', '056', '057' => 'AIRTELTIGO',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.databundleshub.base_url', 'https://www.databundleshub.com'), '/');
        $this->apiKey = config('services.databundleshub.api_key', '');
    }

    public function purchaseData(
        string $msisdn,
        string $network,
        int $capacityMb,
        string $paymentMethod = 'wallet',
        ?int $localOrderId = null,
        ?string $submissionKey = null,
    ): array {
        $capacityGb = $capacityMb >= 1024 ? (string) round($capacityMb / 1024) : (string) $capacityMb;

        $payload = [
            'phoneNumber' => $msisdn,
            'capacity' => $capacityGb,
            'paymentMethod' => $paymentMethod,
        ];

        $startTime = microtime(true);
        $response = $this->sendPost('/api/developer/purchase', $payload);
        $executionTime = (int) round((microtime(true) - $startTime) * 1000);

        $this->logApiCall($localOrderId, $network, $payload, $response, $executionTime);

        $result = $this->normalizeResponse($response);

        if (!empty($result['success'])) {
            $data = $response['data'] ?? [];
            $remaining = $data['remainingBalance'] ?? null;
            if ($remaining !== null) {
                \App\Models\AppSetting::setValue('databundleshub_balance', (string) $remaining);
            }
        }

        return $result;
    }

    public function fetchBalance(): array
    {
        // Try common undocumented balance endpoints
        foreach (['/api/developer/balance', '/api/developer/wallet', '/api/wallet/balance'] as $path) {
            try {
                $result = $this->sendGet($path);
                if (!empty($result['success'])) {
                    $data = $result['data'] ?? [];
                    $balance = $data['balance'] ?? $data['walletBalance'] ?? $data['remainingBalance'] ?? $data['amount'] ?? null;
                    if ($balance !== null) {
                        return [
                            'success' => true,
                            'balance' => (float) $balance,
                            'data' => $data,
                            'http_code' => $result['http_code'] ?? 200,
                            'error' => null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Check AppSetting (updated after every successful purchase)
        $storedBalance = \App\Models\AppSetting::getValue('databundleshub_balance');
        if ($storedBalance !== null && $storedBalance !== '') {
            return [
                'success' => true,
                'balance' => (float) $storedBalance,
                'data' => [],
                'http_code' => 200,
                'error' => null,
                'source' => 'app_setting',
            ];
        }

        // Extract remainingBalance from the most recent order's API response data
        $balance = $this->extractBalanceFromOrders();
        if ($balance !== null) {
            return [
                'success' => true,
                'balance' => $balance,
                'data' => [],
                'http_code' => 200,
                'error' => null,
                'source' => 'last_order',
            ];
        }

        return [
            'success' => false,
            'balance' => null,
            'data' => [],
            'http_code' => 0,
            'error' => 'Place a test order first — your DataBundlesHub wallet balance will appear here automatically after the first purchase',
        ];
    }

    protected function extractBalanceFromOrders(): ?float
    {
        $models = [
            \App\Models\AgentOrder::class => 'agent_order',
            \App\Models\StoreOrder::class => 'store_order',
            \App\Models\Order::class => 'order',
        ];

        $latestOrder = null;
        $latestTimestamp = null;

        foreach ($models as $modelClass => $label) {
            try {
                $order = $modelClass::whereNotNull('api_response_data')
                    ->where('api_response_data', '!=', '[]')
                    ->where('api_response_data', '!=', '"[]"')
                    ->latest()
                    ->first();

                if (!$order) continue;

                $ts = $order->created_at?->timestamp ?? 0;
                if ($latestTimestamp === null || $ts > $latestTimestamp) {
                    $latestTimestamp = $ts;
                    $latestOrder = $order;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (!$latestOrder) return null;

        try {
            $data = is_array($latestOrder->api_response_data)
                ? $latestOrder->api_response_data
                : (is_string($latestOrder->api_response_data) ? json_decode($latestOrder->api_response_data, true) : []);

            if (!is_array($data)) return null;

            return (float) ($data['remainingBalance'] ?? $data['balance'] ?? null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function checkOrderStatus(string $externalId): array
    {
        $url = $this->baseUrl . '/api/check_order_status';
        try {
            $httpResponse = \Illuminate\Support\Facades\Http::timeout(30)->get($url, [
                'order_id' => $externalId,
                'api_key' => $this->apiKey,
            ]);
            
            $body = $httpResponse->json() ?? [];
            $response = [
                'success' => !empty($body['success']),
                'data' => $body,
                'http_code' => $httpResponse->status(),
                'raw_response' => $httpResponse->body(),
                'error' => empty($body['success']) ? ($body['message'] ?? $body['error'] ?? 'API call failed') : null,
            ];
        } catch (\Throwable $e) {
            $response = [
                'success' => false,
                'data' => [],
                'http_code' => 0,
                'raw_response' => '',
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        }

        $body = $response['data'] ?? [];
        $order = $body['order'] ?? $body;
        $status = strtolower((string) ($order['status'] ?? $body['orderStatus'] ?? ''));
        $isCompleted = !empty($order['is_completed']);

        $response['is_delivered'] = $isCompleted || in_array($status, ['delivered', 'completed', 'success', 'fulfilled']);
        $response['is_failed'] = in_array($status, ['failed', 'cancelled', 'reversed']);
        $response['order_status'] = $status;

        return $response;
    }

    public function getTransactions(int $page = 1, int $limit = 20): array
    {
        return $this->sendGet('/api/developer/transactions', [
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    protected function sendPost(string $path, array $payload): array
    {
        $url = $this->baseUrl . $path;

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $httpCode = $response->status();
            $body = $response->json() ?? [];

            $data = $body['data'] ?? [];

            return [
                'success' => !empty($body['success']),
                'data' => $data,
                'http_code' => $httpCode,
                'raw_response' => $response->body(),
                'error' => empty($body['success']) ? ($body['error'] ?? $body['message'] ?? 'API call failed') : null,
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

    protected function sendGet(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;

        try {
            $http = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ]);

            $response = !empty($query) ? $http->get($url, $query) : $http->get($url);
            $httpCode = $response->status();
            $body = $response->json() ?? [];

            return [
                'success' => !empty($body['success']),
                'data' => $body,
                'http_code' => $httpCode,
                'raw_response' => $response->body(),
                'error' => empty($body['success']) ? ($body['message'] ?? $body['error'] ?? 'API call failed') : null,
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

    protected function detectNetworkFromPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        $prefix = substr($digits, 0, 3);

        return self::NETWORK_PREFIXES[$prefix] ?? 'UNKNOWN';
    }

    protected function normalizeResponse(array $response): array
    {
        $data = $response['data'] ?? [];

        $purchaseId = $data['purchaseId'] ?? null;
        $transactionReference = $data['transactionReference'] ?? null;
        $externalId = $purchaseId ?? $transactionReference;
        $price = $data['price'] ?? null;

        return [
            'success' => $response['success'] ?? false,
            'data' => $data,
            'http_code' => $response['http_code'] ?? 0,
            'raw_response' => $response['raw_response'] ?? null,
            'error' => $response['error'] ?? null,
            'external_transaction_id' => $externalId,
            'external_reference' => $transactionReference ?? $externalId,
            'base_cost' => $price !== null ? (float) $price : null,
        ];
    }

    protected function logApiCall(?int $orderId, string $network, ?array $requestData, array $response, int $executionTime): void
    {
        if (!$orderId) return;

        ApiLog::create([
            'order_id' => $orderId,
            'network_type' => $network,
            'api_endpoint' => $this->baseUrl . '/api/developer/purchase',
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

        foreach (self::NETWORK_MAP as $key => $label) {
            if (str_contains($normalized, $key)) {
                return $label;
            }
        }

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
