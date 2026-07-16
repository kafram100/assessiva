<?php

namespace App\Services;

use App\Models\ApiConfig;
use App\Models\ApiLog;
use App\Models\PaymentConfig;
use Illuminate\Support\Facades\Http;

class ExternalAPIService
{
    protected ?ApiConfig $apiConfig;

    public function __construct(protected ?string $network = null)
    {
        if ($network) {
            $this->apiConfig = ApiConfig::where('network_type', $network)->where('is_active', true)->first();
            if (!$this->apiConfig) {
                // Fallback to any active config if the network-specific one isn't found
                $this->apiConfig = ApiConfig::where('is_active', true)->first();
            }
        } else {
            $this->apiConfig = ApiConfig::where('is_active', true)->first();
        }
    }

    public function isGlobalApiEnabled(): bool
    {
        $config = PaymentConfig::where('config_key', 'api_enabled')->first();
        return $config && $config->config_value === '1';
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

    public function validatePhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        return strlen($digits) >= 9 ? $digits : null;
    }

    public function purchaseData(
        string $msisdn,
        string $network,
        int $capacityMb,
        string $paymentMethod = 'wallet',
        ?int $localOrderId = null,
        ?string $submissionKey = null,
    ): array {
        if (!$this->isGlobalApiEnabled()) {
            return ['success' => false, 'error' => 'Global API integration is disabled', 'data' => null];
        }

        if (!$this->apiConfig) {
            return ['success' => false, 'error' => "No active API configuration found for network: {$network}", 'data' => null];
        }

        $requestBody = $this->buildRequestBody($msisdn, $network, $capacityMb, $paymentMethod, $localOrderId, $submissionKey);
        $headers = $this->buildHeaders();

        $startTime = microtime(true);
        $retryAttempts = max(0, (int) ($this->apiConfig->retry_attempts ?? 0));
        $response = $this->sendRequest($requestBody, $headers);
        $attempt = 0;
        while (!$response['success'] && $attempt < $retryAttempts) {
            $attempt++;
            usleep(500_000);
            $response = $this->sendRequest($requestBody, $headers);
        }
        $executionTime = (int) round((microtime(true) - $startTime) * 1000);

        $identifiers = $this->extractIdentifiers($response);

        $this->logApiCall($localOrderId, $network, $requestBody, $response, $executionTime);

        return array_merge($response, $identifiers);
    }

    public function checkOrderStatus(string $externalId, ?int $localOrderId = null): array
    {
        if (!$this->isGlobalApiEnabled()) {
            return ['success' => false, 'error' => 'Global API integration is disabled', 'is_delivered' => false];
        }

        if (!$this->apiConfig) {
            return ['success' => false, 'error' => 'No active API configuration found', 'is_delivered' => false];
        }

        $endpoint = $this->apiConfig->status_endpoint;
        if (!$endpoint) {
            return ['success' => false, 'error' => 'Status endpoint not configured', 'is_delivered' => false];
        }

        $endpoint = str_replace(
            ['{order_id}', '{api_key}', '{api_secret}', '{network}'],
            [$externalId, $this->apiConfig->api_key ?? '', $this->apiConfig->api_secret ?? '', $this->network ?? ''],
            $endpoint
        );

        $headers = $this->buildHeaders();
        
        // Always use GET for status updates as requested
        $method = 'GET';

        try {
            $http = Http::timeout($this->apiConfig->timeout_seconds ?? 30)->withHeaders($headers);

            $response = $http->get($endpoint);

            $httpCode = $response->status();
            $responseData = $response->json() ?? [];

            // If the response contains a list of orders in 'data' (Agent Portal GH behavior)
            $listData = null;
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                if (isset($responseData['data'][0])) {
                    $listData = $responseData['data'];
                } elseif (isset($responseData['data']['data']) && is_array($responseData['data']['data']) && isset($responseData['data']['data'][0])) {
                    $listData = $responseData['data']['data'];
                }
            }

            if ($listData) {
                $matchedItem = null;
                foreach ($listData as $item) {
                    if (($item['reference'] ?? '') == $externalId || ($item['batch_id'] ?? '') == $externalId || ($item['id'] ?? '') == $externalId || ($item['tracking_id'] ?? '') == $externalId) {
                        $matchedItem = $item;
                        break;
                    }
                }
                if ($matchedItem) {
                    $responseData = $matchedItem;
                }
            }

            $isDelivered = $this->evaluateStatusDelivered($responseData);
            $isFailed = $this->evaluateStatusFailed($responseData);

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'is_delivered' => $isDelivered,
                'is_failed' => $isFailed,
                'data' => $responseData,
                'http_code' => $httpCode,
                'error' => $responseData['error'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'is_delivered' => false,
                'is_failed' => false,
                'data' => null,
                'http_code' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function evaluateStatusDelivered(array $responseData): bool
    {
        $field = $this->apiConfig->response_success_field ?? null;
        if ($field) {
            $value = $this->getNestedValue($responseData, $field);
            if ($value !== null) return (bool) $value;
        }

        $status = $responseData['status'] ?? $responseData['order_status'] ?? '';
        if (is_string($status)) {
            return in_array(strtolower($status), ['delivered', 'completed', 'success', 'fulfilled', 'done']);
        }

        return false;
    }

    protected function evaluateStatusFailed(array $responseData): bool
    {
        $status = $responseData['status'] ?? $responseData['order_status'] ?? '';
        if (is_string($status)) {
            return in_array(strtolower($status), ['failed', 'cancelled', 'rejected', 'error', 'refunded']);
        }

        return false;
    }

    public function fetchBalance(): array
    {
        if (!$this->isGlobalApiEnabled()) {
            return ['success' => false, 'error' => 'Global API integration is disabled'];
        }

        if (!$this->apiConfig) {
            return ['success' => false, 'error' => 'No active API configuration found'];
        }

        $endpoint = $this->apiConfig->balance_endpoint;
        if (!$endpoint) {
            return ['success' => false, 'error' => 'Balance endpoint not configured'];
        }

        $headers = $this->buildHeaders();

        try {
            $http = Http::timeout($this->apiConfig->timeout_seconds ?? 30)
                ->withHeaders($headers);

            $response = $http->get($endpoint);
            $httpCode = $response->status();
            $responseData = $response->json() ?? [];

            $balance = $responseData['balance'] ?? $responseData['data']['balance'] ?? null;

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'balance' => $balance,
                'data' => $responseData,
                'http_code' => $httpCode,
                'error' => $responseData['error'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'balance' => null,
                'data' => null,
                'http_code' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function buildRequestBody(string $msisdn, string $network, int $capacityMb, string $paymentMethod, ?int $localOrderId, ?string $submissionKey = null): ?array
    {
        $template = $this->apiConfig->request_body_template;
        if (is_string($template)) {
            $template = json_decode($template, true);
        }
        if (!is_array($template)) {
            return null;
        }

        $capacityGb = round($capacityMb / 1024, 2);
        $dataGb = (int) floor($capacityMb / 1024);
        if ($dataGb < 1) $dataGb = 1;
        $apiKey = $this->apiConfig->api_key ?? '';
        $apiSecret = $this->apiConfig->api_secret ?? '';

        $apiNetwork = str_replace('_', '', $network);

        $replacements = [
            '{phone}' => $msisdn,
            '{package}' => $capacityMb . 'MB',
            '{amount}' => $capacityMb,
            '{network}' => $apiNetwork,
            '{payment_method}' => $paymentMethod,
            '{order_id}' => $submissionKey ?? (string) ($localOrderId ?? ''),
            '{capacity}' => (string) $capacityGb,
            '{mb}' => $capacityMb,
            '{data_gb}' => (string) $dataGb,
            '{api_key}' => $apiKey,
            '{api_secret}' => $apiSecret,
        ];

        array_walk_recursive($template, function (&$value) use ($replacements) {
            if (!is_string($value)) return;
            $value = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $value
            );
            
            // Force-fix hardcoded "atishare" to "airteltigo" to handle AgentPortalGH API changes
            if (strtolower(trim($value)) === 'atishare' || strtolower(trim($value)) === 'ishare') {
                $value = 'airteltigo';
            }
        });

        $this->normalizeRequestBodyTypes($template);

        return $template;
    }

    protected function normalizeRequestBodyTypes(array &$body): void
    {
        foreach ($body as $key => &$value) {
            if ($key === 'data_gb') {
                $value = (int) $value;
            } elseif (is_array($value)) {
                $this->normalizeRequestBodyTypes($value);
            }
        }
        unset($value);
    }

    protected function buildHeaders(): array
    {
        $headers = $this->apiConfig->request_headers ?? [];
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?? [];
        }
        if (!is_array($headers)) {
            $headers = [];
        }

        $resolved = [];
        foreach ($headers as $key => $value) {
            if (!is_scalar($value)) continue;
            $resolved[$key] = str_replace(
                ['{api_key}', '{api_secret}', '{network}'],
                [$this->apiConfig->api_key ?? '', $this->apiConfig->api_secret ?? '', $this->network ?? ''],
                (string) $value
            );
        }

        return $resolved;
    }

    protected function sendRequest(?array $requestBody, array $headers): array
    {
        $endpoint = $this->apiConfig->api_endpoint;
        $method = strtoupper($this->apiConfig->request_method ?? 'POST');
        $timeout = $this->apiConfig->timeout_seconds ?? 30;

        try {
            $http = Http::timeout($timeout)->withHeaders($headers);

            if ($method === 'GET') {
                $response = $http->get($endpoint, $requestBody ?? []);
            } else {
                $http = $http->withBody(json_encode($requestBody), 'application/json');
                $response = $http->send($method, $endpoint);
            }

            $httpCode = $response->status();
            $body = $response->body();
            $responseData = $response->json() ?? [];

            $isSuccess = $this->evaluateSuccess($responseData, $httpCode);
            $extractedData = $this->extractDataField($responseData);
            $errorMessage = $this->extractError($responseData, $isSuccess);

            return [
                'success' => $isSuccess,
                'data' => $extractedData,
                'http_code' => $httpCode,
                'raw_response' => $body,
                'error' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => null,
                'http_code' => 0,
                'raw_response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function evaluateSuccess(array $responseData, int $httpCode): bool
    {
        $field = $this->apiConfig->response_success_field ?? 'success';
        $value = $this->getNestedValue($responseData, $field);
        return $value !== null ? (bool) $value : ($httpCode >= 200 && $httpCode < 300);
    }

    protected function extractDataField(array $responseData): array
    {
        $field = $this->apiConfig->response_data_field ?? null;
        if (!$field) return $responseData;

        $value = $this->getNestedValue($responseData, $field);
        return is_array($value) ? $value : $responseData;
    }

    protected function extractError(array $responseData, bool $isSuccess): ?string
    {
        if ($isSuccess) return null;

        $field = $this->apiConfig->response_error_field ?? null;
        if ($field) {
            $value = $this->getNestedValue($responseData, $field);
            if (is_scalar($value)) return (string) $value;
        }

        return $responseData['message'] ?? $responseData['error'] ?? 'API call failed';
    }

    protected function getNestedValue(?array $data, string $field): mixed
    {
        if (!$data) return null;
        $keys = explode('.', $field);
        $current = $data;
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) return null;
            $current = $current[$key];
        }
        return $current;
    }

    protected function extractIdentifiers(array $response): array
    {
        $transactionId = null;
        $reference = null;

        if (!empty($response['data']) && is_array($response['data'])) {
            $payload = $response['data'];
            $transactionId = $payload['purchaseId'] ?? $payload['order_id'] ?? $payload['transaction_id'] ?? $payload['batch_id'] ?? null;
            $reference = $payload['transactionReference'] ?? $payload['reference'] ?? null;
        }

        if (!$transactionId && !empty($response['raw_response'])) {
            $raw = json_decode($response['raw_response'], true) ?? [];
            $transactionId = $raw['purchaseId'] ?? $raw['order_id'] ?? $raw['transaction_id'] ?? $raw['batch_id'] ?? $raw['data']['purchaseId'] ?? $raw['data']['order_id'] ?? $raw['data']['batch_id'] ?? null;
            $reference = $raw['transactionReference'] ?? $raw['reference'] ?? $raw['data']['transactionReference'] ?? $raw['data']['reference'] ?? null;
        }

        if (!$transactionId && $reference) {
            $transactionId = $reference;
        }

        return [
            'external_transaction_id' => $transactionId,
            'external_reference' => $reference,
        ];
    }

    protected function logApiCall(?int $orderId, string $network, ?array $requestData, array $response, int $executionTime): void
    {
        if (!$orderId) return;

        ApiLog::create([
            'order_id' => $orderId,
            'network_type' => $network,
            'api_endpoint' => $this->apiConfig?->api_endpoint,
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
}
