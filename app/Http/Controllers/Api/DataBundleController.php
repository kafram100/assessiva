<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DataBundle\DataBundleRouter;
use App\Services\DataBundle\DataFlowProvider;
use App\Services\DataBundle\GHDataConnectProvider;
use Illuminate\Http\Request;

class DataBundleController extends Controller
{
    public function __construct(
        protected DataBundleRouter $router,
    ) {}

    public function purchaseBundle(Request $request): array
    {
        $validated = $request->validate([
            'network' => 'required|string|in:mtn,telecel,atishare,atbigtime',
            'reference' => 'required|string',
            'msisdn' => 'required|string',
            'capacity' => 'required|numeric|min:1',
        ]);

        $capacityMb = $validated['capacity'] > 100
            ? (int) $validated['capacity']
            : (int) round($validated['capacity'] * 1024);

        $result = $this->router->purchaseData(
            msisdn: $validated['msisdn'],
            network: $validated['network'],
            capacityMb: $capacityMb,
            paymentMethod: 'api',
            submissionKey: $validated['reference'],
        );

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['success'] ? 'Order placed successfully' : ($result['error'] ?? 'Order failed'),
            'data' => [
                'reference' => $result['external_reference'] ?? $validated['reference'],
                'recipient' => $validated['msisdn'],
                'capacity' => $validated['capacity'],
                'price' => $result['data']['price'] ?? 0,
                'balance' => $result['data']['balance'] ?? null,
                'status' => $result['success'] ? 'pending' : 'failed',
            ],
        ];
    }

    public function createIshareBundleOrder(Request $request): array
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'msisdn' => 'required|string',
            'capacity' => 'required|integer|min:1',
        ]);

        $provider = app(GHDataConnectProvider::class);
        $result = $provider->createIshareBundleOrder(
            msisdn: $validated['msisdn'],
            capacityMb: (int) $validated['capacity'],
            reference: $validated['reference'],
        );

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['success'] ? 'iShare order placed' : ($result['error'] ?? 'Order failed'),
            'data' => $result['data'],
        ];
    }

    public function getWalletBalance(): array
    {
        $result = $this->router->fetchBalance();

        return [
            'success' => $result['success'] ?? false,
            'data' => [
                'balance' => $result['balance'] ?? '0.00',
            ],
        ];
    }

    public function checkOrderStatus(string $reference): array
    {
        return $this->router->checkOrderStatus($reference);
    }

    public function getAllNetworks(): array
    {
        $provider = app(GHDataConnectProvider::class);
        $result = $provider->getAllNetworks();

        return $result;
    }

    // --- DataFlow-specific endpoints ---

    public function dataflowCreateOrder(Request $request): array
    {
        $validated = $request->validate([
            'beneficiary_number' => 'required|string',
            'network_id' => 'required|integer|in:5,6,7,8',
            'size' => 'required|string',
        ]);

        $networkMap = [5 => 'mtn', 6 => 'telecel', 7 => 'atishare', 8 => 'bigtime'];
        $network = $networkMap[(int) $validated['network_id']] ?? 'mtn';
        $capacityMb = app(DataFlowProvider::class)->convertPackageSize($validated['size']);

        $result = $this->router->purchaseData(
            msisdn: $validated['beneficiary_number'],
            network: $network,
            capacityMb: $capacityMb,
            paymentMethod: 'api',
        );

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['success'] ? 'Order created successfully' : ($result['error'] ?? 'Order failed'),
            'order' => $result['data'],
        ];
    }

    public function dataflowOrders(): array
    {
        $provider = app(DataFlowProvider::class);
        return $provider->getOrders();
    }

    public function dataflowTransactions(): array
    {
        $provider = app(DataFlowProvider::class);
        return $provider->getTransactions();
    }

    public function dataflowTransaction(string $id): array
    {
        $provider = app(DataFlowProvider::class);
        return $provider->getTransaction($id);
    }

    public function dataflowTransactionStatus(): array
    {
        $provider = app(DataFlowProvider::class);
        return $provider->getTransactionStatus();
    }

    public function dataflowAfaProducts(): array
    {
        $provider = app(DataFlowProvider::class);
        return $provider->getAfaProducts();
    }

    public function dataflowCreateAfa(Request $request): array
    {
        $validated = $request->validate([
            'afa_product_id' => 'required|integer',
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'dob' => 'required|string',
            'occupation' => 'required|string',
            'region' => 'required|string',
        ]);

        $provider = app(DataFlowProvider::class);
        $result = $provider->createAfaOrder($validated);

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['success'] ? 'AFA order created' : ($result['error'] ?? 'Order failed'),
            'data' => $result['data'],
        ];
    }

    public function getActiveProvider(): array
    {
        return [
            'success' => true,
            'data' => [
                'provider' => $this->router->getActiveProvider(),
                'available' => array_keys(DataBundleRouter::getAvailableProviders()),
            ],
        ];
    }
}
