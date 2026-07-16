<?php

namespace App\Services\DataBundle;

use App\Models\AppSetting;
use App\Services\ExternalAPIService;

class DataBundleRouter
{
    public const PROVIDER_LEGACY = 'legacy';
    public const PROVIDER_GHDATACONNECT = 'ghdataconnect';
    public const PROVIDER_DATAFLOW = 'dataflow';
    public const PROVIDER_DATABUNDLESHUB = 'databundleshub';

    protected ?string $cachedProvider = null;
    protected ?object $resolvedInstance = null;

    public function getActiveProvider(): string
    {
        if ($this->cachedProvider === null) {
            $this->cachedProvider = AppSetting::getValue('data_provider', self::PROVIDER_DATABUNDLESHUB);
        }
        return $this->cachedProvider;
    }

    public function refreshProvider(): void
    {
        $this->cachedProvider = null;
        $this->resolvedInstance = null;
    }

    public function isNetworkActive(string $network): bool
    {
        $network = strtolower(trim(str_replace('_MASHUP', '', $network)));
        
        if (in_array($network, ['mtn', 'vodafone', 'airteltigo'])) {
            return AppSetting::getValue("{$network}_status", '1') === '1';
        }
        
        // If it's an unknown network type, assume active to prevent blocking
        return true;
    }

    protected function resolve(): object
    {
        if ($this->resolvedInstance !== null) {
            return $this->resolvedInstance;
        }

        $provider = $this->getActiveProvider();

        $this->resolvedInstance = match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class),
            default => new ExternalAPIService,
        };

        return $this->resolvedInstance;
    }

    public function purchaseData(
        string $msisdn,
        string $network,
        int $capacityMb,
        string $paymentMethod = 'wallet',
        ?int $localOrderId = null,
        ?string $submissionKey = null,
    ): array {
        $provider = $this->getActiveProvider();

        $svc = match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class),
            default => new ExternalAPIService($network),
        };

        $result = $svc->purchaseData(
            msisdn: $msisdn,
            network: $network,
            capacityMb: $capacityMb,
            paymentMethod: $paymentMethod,
            localOrderId: $localOrderId,
            submissionKey: $submissionKey,
        );

        if (empty($result['success']) && (!isset($result['error']) || $result['error'] === 'API call failed' || $result['error'] === 'Provider submission failed')) {
            $result['error'] = self::extractDetailedError($result['raw_response'] ?? null, $result['data'] ?? []);
        }

        return $result;
    }

    public static function extractDetailedError(?string $rawResponse, array $data = []): string
    {
        $payloadsToSearch = [];
        if (!empty($data)) {
            $payloadsToSearch[] = $data;
        }
        if ($rawResponse) {
            $decoded = json_decode($rawResponse, true);
            if (is_array($decoded)) {
                $payloadsToSearch[] = $decoded;
            }
        }

        foreach ($payloadsToSearch as $payload) {
            if (isset($payload['rejected']) && is_array($payload['rejected']) && count($payload['rejected']) > 0) {
                return $payload['rejected'][0]['reason'] ?? 'Order rejected by provider';
            }
            if (isset($payload['errors']) && is_array($payload['errors'])) {
                return collect($payload['errors'])->flatten()->first() ?? 'Validation failed';
            }
            if (!empty($payload['error']) && is_string($payload['error'])) {
                return $payload['error'];
            }
            if (!empty($payload['message']) && is_string($payload['message'])) {
                return $payload['message'];
            }
        }

        return 'API call failed';
    }

    public function fetchBalance(?string $network = null): array
    {
        $provider = $this->getActiveProvider();

        $svc = match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class),
            default => new ExternalAPIService($network),
        };

        return $svc->fetchBalance();
    }

    public function normalizeNetwork(string $network): string
    {
        $provider = $this->getActiveProvider();

        return match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class)->normalizeNetwork($network),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class)->normalizeNetwork($network),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class)->normalizeNetwork($network),
            default => $this->legacyNormalizeNetwork($network),
        };
    }

    public function isValidNetworkPrefix(string $phone, string $network): bool
    {
        $phone = preg_replace('/^233/', '0', $phone);
        $phone = preg_replace('/\D/', '', $phone);
        
        if (strlen($phone) < 3) return false;
        
        $prefix = substr($phone, 0, 3);
        $normalized = strtolower(trim(str_replace('_MASHUP', '', $network)));
        
        // MTN Ghana
        if (in_array($normalized, ['mtn', 'yello'])) {
            return in_array($prefix, ['024', '025', '053', '054', '055', '059']);
        }
        
        // Telecel (Vodafone) Ghana
        if (in_array($normalized, ['telecel', 'vodafone', 'voda'])) {
            return in_array($prefix, ['020', '050']);
        }
        
        // AirtelTigo (AT) Ghana
        if (in_array($normalized, ['airteltigo', 'airtel_tigo', 'at', 'airtel', 'tigo', 'atishare', 'ishare'])) {
            return in_array($prefix, ['026', '056', '027', '057']);
        }
        
        return true;
    }

    public function validatePhoneNumber(string $phone): ?string
    {
        $provider = $this->getActiveProvider();

        $svc = match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class),
            default => new ExternalAPIService,
        };

        return $svc->validatePhoneNumber($phone);
    }

    public function isCredentialsConfigured(): bool
    {
        $provider = $this->getActiveProvider();

        return match ($provider) {
            self::PROVIDER_GHDATACONNECT => !empty(config('services.ghdataconnect.api_key')),
            self::PROVIDER_DATAFLOW => !empty(config('services.dataflow.api_token')) || (!empty(config('services.dataflow.email')) && !empty(config('services.dataflow.password'))),
            self::PROVIDER_DATABUNDLESHUB => !empty(config('services.databundleshub.api_key')),
            default => true,
        };
    }

    public function getMissingCredentialsMessage(): string
    {
        $provider = $this->getActiveProvider();

        return match ($provider) {
            self::PROVIDER_GHDATACONNECT => 'GHDataConnect is selected but no API key is configured. Set GHDATACONNECT_API_KEY in your .env file.',
            self::PROVIDER_DATAFLOW => 'DataFlow Ghana is selected but no credentials are configured. Set DATAFLOW_API_TOKEN or DATAFLOW_EMAIL + DATAFLOW_PASSWORD in your .env file.',
            self::PROVIDER_DATABUNDLESHUB => 'DataBundlesHub is selected but no API key is configured. Set DATABUNDLESHUB_API_KEY in your .env file.',
            default => 'The selected provider is not configured.',
        };
    }

    public function checkOrderStatus(string $externalId, ?string $network = null): array
    {
        $provider = $this->getActiveProvider();

        $svc = match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class),
            default => new ExternalAPIService($network ? $this->legacyNormalizeNetwork($network) : null),
        };

        return $svc->checkOrderStatus($externalId);
    }

    public function convertPackageSize(string $bundleLabel): int
    {
        $provider = $this->getActiveProvider();

        $svc = match ($provider) {
            self::PROVIDER_GHDATACONNECT => app(GHDataConnectProvider::class),
            self::PROVIDER_DATAFLOW => app(DataFlowProvider::class),
            self::PROVIDER_DATABUNDLESHUB => app(DataBundleHubProvider::class),
            default => new ExternalAPIService,
        };

        return $svc->convertPackageSize($bundleLabel);
    }

    public static function getAvailableProviders(): array
    {
        return [
            self::PROVIDER_LEGACY => 'Legacy (ApiConfig-based)',
            self::PROVIDER_GHDATACONNECT => 'GHDataConnect',
            self::PROVIDER_DATAFLOW => 'DataFlow Ghana',
            self::PROVIDER_DATABUNDLESHUB => 'DataBundlesHub',
        ];
    }

    protected function legacyNormalizeNetwork(string $network): string
    {
        $normalized = strtolower(trim($network));
        if (str_contains($normalized, 'mtn') || str_contains($normalized, 'yello')) return 'mtn';
        if (str_contains($normalized, 'telecel') || str_contains($normalized, 'vodafone') || str_contains($normalized, 'voda')) return 'telecel';
        if (str_contains($normalized, 'airtel') || str_contains($normalized, 'tigo') || str_contains($normalized, 'atishare') || str_contains($normalized, 'ishare')) return 'airtel_tigo';
        return $normalized;
    }
}
