<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDataBundleOrder;
use App\Models\AgentPackage;
use App\Models\CustomerPackage;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Payment\Gateways\PaystackGateway;
use App\Services\Payment\PaymentRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicOrderController extends Controller
{
    public function __invoke(Request $request)
    {
        return match ($request->route()->getActionMethod()) {
            'packages' => $this->packages($request),
            'checkout' => $this->checkout($request),
            'show' => $this->show($request),
            'track' => $this->track($request),
            default => response()->json(['message' => 'Not found'], 404),
        };
    }

    public function packages()
    {
        $packages = CustomerPackage::where('is_active', true)
            ->orderByRaw("CASE WHEN network_type = 'MTN' THEN 0 WHEN network_type = 'VODAFONE' THEN 1 WHEN network_type = 'AIRTELTIGO' THEN 2 ELSE 3 END")
            ->orderBy('price')
            ->get()
            ->groupBy('network_type');

        $mashup = AgentPackage::where('manual_fulfillment', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn ($pkg) => [
                'id' => $pkg->id,
                'network_type' => 'MTN_MASHUP',
                'package_name' => $pkg->package_name,
                'package_size_gb' => (float) $pkg->package_size_gb,
                'voice_minutes' => (int) $pkg->voice_minutes,
                'price' => (float) $pkg->price,
                'non_expiring' => (bool) $pkg->non_expiring,
            ]);

        $packages['MTN_MASHUP'] = $mashup;

        $networks = $packages->keys()->filter(fn ($key) => $key !== 'MTN_MASHUP')->values();
        $networks->push('MTN_MASHUP');

        return response()->json([
            'success' => true,
            'data' => $packages,
            'networks' => $networks,
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'customer_phone' => 'required|string|max:20',
            'package_id' => 'required|integer',
            'beneficiary_number' => 'required|string|max:20',
            'is_mashup' => 'boolean',
        ]);

        $isMashup = $validated['is_mashup'] ?? false;

        if ($isMashup) {
            $package = AgentPackage::where('manual_fulfillment', true)
                ->where('is_active', true)
                ->findOrFail($validated['package_id']);
        } else {
            $request->validate([
                'package_id' => 'required|exists:customer_packages,id',
            ]);
            $package = CustomerPackage::findOrFail($validated['package_id']);
        }

        $networkType = $isMashup ? 'MTN' : $package->network_type;
        if (!app(\App\Services\DataBundle\DataBundleRouter::class)->isNetworkActive($networkType)) {
            return response()->json([
                'success' => false,
                'message' => "The {$networkType} network is currently unavailable. Please try again later.",
            ], 503);
        }

        if (!app(\App\Services\DataBundle\DataBundleRouter::class)->isValidNetworkPrefix($validated['beneficiary_number'], $networkType)) {
            return response()->json([
                'success' => false,
                'message' => "The phone number provided does not match the selected {$networkType} network.",
            ], 422);
        }

        $paymentRef = 'PAY-' . strtoupper(Str::random(16));

        $packageSize = $isMashup
            ? ($package->package_size_gb > 0 ? $package->package_size_gb . ' GB' : '')
            : $package->package_size;

        $order = Order::create([
            'user_id' => \App\Models\User::where('role', 'admin')->value('id') ?? 1,
            'package_size' => $packageSize,
            'package_name' => $package->package_name,
            'amount' => $package->price,
            'beneficiary_number' => $validated['beneficiary_number'],
            'network_type' => $isMashup ? 'MTN_MASHUP' : $package->network_type,
            'status' => 'unpaid',
            'reference' => $paymentRef,
            'momo_number' => $validated['customer_phone'],
        ]);

        $router = app(PaymentRouter::class);
        $gateway = $router->forContext(PaymentRouter::CONTEXT_PUBLIC_ORDER);

        $amount = $package->price;
        $processingFee = 0;

        if ($gateway instanceof PaystackGateway) {
            $processingFee = round($amount * 0.02, 2);
            $order->update(['processing_fee' => $processingFee]);
        }

        $customerEmail = $request->input('customer_email', config('app.default_customer_email', 'customer@example.com'));
        $result = $gateway->initiate([
            'checkout' => true,
            'email' => $customerEmail,
            'amount' => $amount + $processingFee,
            'reference' => $paymentRef,
            'callback_url' => url('/api/payment/callback'),
            'redirect_url' => url('/api/payment/callback?reference=' . $paymentRef),
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);

        if (!$result['success']) {
            $order->update(['status' => 'failed']);
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

    public function show($id)
    {
        $order = Order::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order->load('statusHistory'),
        ]);
    }

    public function track($reference)
    {
        $order = Order::where('tracking_key', $reference)
            ->orWhere('order_reference', $reference)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load('statusHistory'),
        ]);
    }

    public function paymentCallback(Request $request)
    {
        $router = app(PaymentRouter::class);
        $gateway = $router->forContext(PaymentRouter::CONTEXT_PUBLIC_ORDER);

        $result = $gateway->handleCallback($request);

        if (!$result['success']) {
            return redirect()->to('/?payment=failed');
        }

        $reference = $result['reference'] ?? $request->query('reference');
        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            return redirect()->to('/?payment=failed&error=order_not_found');
        }

        if ($order->status !== 'unpaid') {
            return redirect()->to("/order/{$order->id}/success");
        }

        $order->update([
            'order_reference' => 'ORD-' . strtoupper(Str::random(12)),
            'tracking_key' => strtoupper(Str::random(8)),
            'status' => 'payment_confirmed',
            'external_transaction_id' => $result['transaction_id'] ?? null,
            'external_reference' => $result['reference'] ?? $reference,
            'payment_method' => $result['channel'] ?? 'paystack',
            'status_updated_at' => now(),
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => 'unpaid',
            'new_status' => 'payment_confirmed',
            'change_reason' => 'Payment confirmed',
            'changed_by' => 'system',
        ]);

        if ($order->network_type === 'MTN') {
            ProcessDataBundleOrder::dispatch($order);
        }

        return redirect()->to("/order/{$order->id}/success");
    }
}
