<?php

namespace App\Http\Controllers;

use App\Models\AgentPackage;
use App\Models\AgentPackagePricing;
use App\Models\AgentStoreSetting;
use App\Models\StoreOrder;
use App\Models\StoreOrderStatusHistory;
use App\Services\DataBundle\DataBundleRouter;
use App\Services\Payment\Gateways\PaystackGateway;
use App\Services\Payment\PaymentRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StorefrontController extends Controller
{
    public function index($slug)
    {
        $store = AgentStoreSetting::where('store_slug', $slug)->first();

        if (!$store) {
            return Inertia::render('Storefront', [
                'storeClosed' => true,
                'store' => null,
                'closedReason' => 'Store not found.',
            ]);
        }

        if (!$store->is_active) {
            return Inertia::render('Storefront', [
                'storeClosed' => true,
                'store' => [
                    'name' => $store->store_name,
                    'slug' => $store->store_slug,
                    'theme_color' => $store->theme_color ?? '#1d4ed8',
                    'logo_url' => $store->logo_url,
                ],
                'closedReason' => 'This store is currently closed. Please check back later.',
            ]);
        }

        $dealer = $store->agent;

        $paymentStatus = request('payment');

        $refCode = request('ref');
        $SubAgent = null;
        if ($refCode) {
            $SubAgent = \App\Models\SubAgent::where('ref_code', $refCode)
                ->where('agent_id', $dealer->id)
                ->where('is_active', true)
                ->first();
        }

        $packages = AgentPackage::with(['agentPackagePricing' => fn ($q) => $q
                ->where('agent_id', $dealer->id)
                ->where('is_active', true)])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('role', 'dealer')->orWhere('role', 'all');
            })
            ->orderByRaw("CASE WHEN network_type = 'MTN' THEN 0 WHEN network_type = 'VODAFONE' THEN 1 WHEN network_type = 'AIRTELTIGO' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn ($pkg) => $pkg->network_type . '_' . $pkg->package_size_gb)
            ->map(function ($group) {
                return $group->firstWhere('role', 'dealer') ?? $group->first();
            })
            ->values()
            ->sortBy('sort_order')
            ->filter(function ($pkg) use ($dealer) {
                if ($pkg->manual_fulfillment) return false;
                $customPricing = $pkg->agentPackagePricing->first();
                if (!$customPricing) return false;
                return (float) $customPricing->customer_price !== (float) $pkg->price;
            })
            ->map(function ($pkg) {
                $customPricing = $pkg->agentPackagePricing->first();
                return [
                    'id' => $pkg->id,
                    'network_type' => $pkg->network_type,
                    'package_name' => $pkg->package_name,
                    'package_size' => $pkg->package_size_gb
                        ? ($pkg->package_size_gb . ' GB')
                        : ($pkg->package_size_mb . ' MB'),
                    'price' => (float) ($customPricing?->customer_price ?? $pkg->price),
                    'sort_order' => $pkg->sort_order,
                ];
            })
            ->groupBy('network_type');

        return Inertia::render('Storefront', [
            'paymentStatus' => $paymentStatus,
            'store' => [
                'name' => $store->store_name,
                'slug' => $store->store_slug,
                'description' => $store->store_description,
                'theme_color' => $store->theme_color ?? '#1d4ed8',
                'accent_color' => $store->accent_color ?? '#f59e0b',
                'logo_url' => $store->logo_url,
                'contact_phone' => $store->contact_phone,
                'contact_whatsapp' => $store->contact_whatsapp,
                'hero_headline' => $store->hero_headline,
                'announcement_text' => $store->announcement_text,
                'footer_tagline' => $store->footer_tagline,
            ],
            'packagesData' => $packages,
            'networks' => $packages->keys(),
            'dealer_name' => $dealer->full_name ?? $dealer->username,
            'ref_code' => $refCode,
            'sub_agent_name' => $SubAgent?->full_name,
        ]);
    }

    public function checkout(Request $request, $slug)
    {
        $store = AgentStoreSetting::where('store_slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'This store is currently closed.',
            ], 404);
        }

        $dealer = $store->agent;

        $validated = $request->validate([
            'package_id' => 'required|exists:agent_packages,id',
            'customer_phone' => 'required|string|max:20',
            'customer_name' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email|max:100',
            'ref_code' => 'nullable|string|max:20',
        ]);

        $package = AgentPackage::findOrFail($validated['package_id']);

        if (!app(\App\Services\DataBundle\DataBundleRouter::class)->isNetworkActive($package->network_type)) {
            return response()->json([
                'success' => false,
                'message' => "The {$package->network_type} network is currently unavailable. Please try again later.",
            ], 503);
        }

        if (!app(\App\Services\DataBundle\DataBundleRouter::class)->isValidNetworkPrefix($validated['customer_phone'], $package->network_type)) {
            return response()->json([
                'success' => false,
                'message' => "The phone number provided does not match the selected {$package->network_type} network.",
            ], 422);
        }

        $customPricing = AgentPackagePricing::where('agent_id', $dealer->id)
            ->where('package_id', $package->id)
            ->where('is_active', true)
            ->first();

        $price = (float) ($customPricing?->customer_price ?? $package->price);

        $SubAgent = null;
        $SubAgentId = null;
        $SubAgentProfit = 0;
        $commissionRate = 0;
        if (!empty($validated['ref_code'])) {
            $SubAgent = \App\Models\SubAgent::where('ref_code', $validated['ref_code'])
                ->where('agent_id', $dealer->id)
                ->where('is_active', true)
                ->first();
            if ($SubAgent) {
                $SubAgentId = $SubAgent->id;
                $commissionRate = (float) $SubAgent->commission_rate / 100;
                $SubAgentProfit = round($price * $commissionRate, 2);
            }
        }

        $agentProfit = round($price - $SubAgentProfit, 2);

        $orderRef = 'STR-' . strtoupper(Str::random(12));
        $paymentRef = 'PAY-' . strtoupper(Str::random(16));

        $order = StoreOrder::create([
            'order_ref' => $orderRef,
            'tracking_id' => null,
            'agent_id' => $dealer->id,
            'sub_agent_id' => $SubAgentId,
            'customer_phone' => $validated['customer_phone'],
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_email' => $validated['customer_email'] ?? null,
            'package_id' => $package->id,
            'network_type' => $package->network_type,
            'package_size_gb' => $package->package_size_gb ?? ($package->package_size_mb / 1024),
            'base_price' => $price,
            'sold_price' => $price,
            'agent_profit' => $agentProfit,
            'sub_agent_profit' => $SubAgentProfit,
            'your_profit' => $price,
            'payment_method' => 'paystack',
            'payment_reference' => $paymentRef,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        StoreOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'notes' => 'Order created via storefront',
            'created_by' => 'storefront',
        ]);

        $router = app(PaymentRouter::class);
        $gateway = $router->forDealerStore($dealer->id);

        $processingFee = 0;
        if ($gateway instanceof PaystackGateway) {
            $processingFee = round($price * 0.02, 2);
        }

        $total = round($price + $processingFee, 2);

        if ($processingFee > 0) {
            $order->update(['processing_fee' => $processingFee]);
        }

        $result = $gateway->initiate([
            'checkout' => true,
            'email' => $validated['customer_email'] ?? $dealer->email,
            'amount' => $total,
            'reference' => $paymentRef,
            'callback_url' => route('store.payment.callback', ['slug' => $slug]),
            'metadata' => [
                'order_id' => $order->id,
                'type' => 'store_order',
                'agent_id' => $dealer->id,
                'sub_agent_id' => $SubAgentId,
            ],
        ]);

        if (!$result['success']) {
            $order->update(['status' => 'failed', 'payment_status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initialize payment',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'authorization_url' => $result['authorization_url'],
            ],
        ]);
    }

    public function paymentCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->input('reference');
        $cancelled = $request->query('cancelled');

        if ($cancelled) {
            $slug = '';
            if ($reference) {
                $order = StoreOrder::where('payment_reference', $reference)->first();
                if ($order && $order->payment_status !== 'paid') {
                    $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
                    StoreOrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => 'cancelled',
                        'notes' => 'Customer cancelled payment on Paystack',
                        'created_by' => 'system',
                    ]);
                    $slug = $order->agent?->storeSettings?->store_slug ?? '';
                }
            }
            if (!$slug) {
                $querySlug = $request->query('slug', '');
                $store = $querySlug ? AgentStoreSetting::where('store_slug', $querySlug)->where('is_active', true)->first() : null;
                $slug = $store?->store_slug ?? '';
            }
            return $slug ? redirect()->to("/store/{$slug}?payment=cancelled") : redirect()->to('/');
        }

        if (!$reference) {
            return redirect()->to('/?payment=failed&error=no_reference');
        }

        $order = StoreOrder::where('payment_reference', $reference)->first();

        if (!$order) {
            return redirect()->to('/?payment=failed&error=order_not_found');
        }

        $dealer = $order->agent;

        $router = app(PaymentRouter::class);
        $gateway = $router->forDealerStore($dealer->id);

        $result = $gateway->verify($reference);

        if (!$result['success']) {
            $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            StoreOrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'notes' => 'Payment verification failed',
                'created_by' => 'system',
            ]);
            $slug = $dealer->storeSettings?->store_slug ?? '';
            return $slug ? redirect()->to("/store/{$slug}?payment=failed") : redirect()->to('/?payment=failed');
        }

        $trackingId = strtoupper(Str::random(8));

        $isManual = $order->package?->manual_fulfillment ?? false;

        $order->update([
            'tracking_id' => $trackingId,
            'status' => $isManual ? 'pending' : 'processing',
            'payment_status' => 'paid',
            'external_transaction_id' => $result['transaction_id'] ?? null,
            'external_reference' => $result['reference'] ?? $reference,
            'paid_at' => now(),
            'payment_method' => $result['channel'] ?? 'paystack',
        ]);

        StoreOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $isManual ? 'pending' : 'processing',
            'notes' => $isManual ? 'Payment confirmed — manual fulfillment' : 'Payment confirmed via Paystack',
            'created_by' => 'system',
        ]);

        if (!$isManual && app(DataBundleRouter::class)->isCredentialsConfigured()) {
            $packageName = $order->package_size_gb ? ($order->package_size_gb . ' GB') : '1 GB';
            $capacityMb = app(DataBundleRouter::class)->convertPackageSize($packageName);
            $network = app(DataBundleRouter::class)->normalizeNetwork($order->network_type);

            $providerResult = app(DataBundleRouter::class)->purchaseData(
                msisdn: $order->customer_phone,
                network: $network,
                capacityMb: $capacityMb,
                paymentMethod: 'storefront_paid',
                localOrderId: $order->id,
                submissionKey: 'store:' . $order->id,
            );

            $status = !empty($providerResult['success']) ? 'processing' : 'failed';
            $order->update([
                'status' => $status,
                'failure_reason' => $status === 'failed' ? ($providerResult['error'] ?? 'API submission failed') : null,
                'base_cost' => $providerResult['base_cost'] ?? null,
                'external_transaction_id' => $providerResult['external_transaction_id'] ?? null,
                'external_reference' => $providerResult['external_reference'] ?? null,
                'api_response_data' => $providerResult['data'] ?? null,
            ]);
        }

        return redirect()->to("/store/{$dealer->storeSettings?->store_slug}/order/{$order->id}/success");
    }

    public function orderSuccess($slug, $orderId)
    {
        $orderId = (int) $orderId;
        $store = AgentStoreSetting::where('store_slug', $slug)->firstOrFail();
        $order = StoreOrder::where('id', $orderId)
            ->where('agent_id', $store->agent_id)
            ->where('payment_status', 'paid')
            ->firstOrFail();

        return Inertia::render('StorefrontSuccess', [
            'store' => [
                'name' => $store->store_name,
                'slug' => $store->store_slug,
            ],
            'order' => [
                'order_ref' => $order->order_ref,
                'tracking_id' => $order->tracking_id,
                'customer_phone' => $order->customer_phone,
                'package_size_gb' => (float) $order->package_size_gb,
                'sold_price' => (float) $order->sold_price,
                'processing_fee' => (float) $order->processing_fee,
                'status' => $order->status,
                'failure_reason' => $order->failure_reason,
            ],
        ]);
    }
}
