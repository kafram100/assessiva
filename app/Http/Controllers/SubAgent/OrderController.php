<?php

namespace App\Http\Controllers\SubAgent;

use App\Http\Controllers\Controller;
use App\Models\AgentPackage;
use App\Models\AgentPackagePricing;
use App\Models\StoreOrder;
use App\Models\SubAgent;
use App\Models\SubAgentBalanceHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent || !$SubAgent->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or account suspended.',
            ], 403);
        }

        $validated = $request->validate([
            'package_id' => 'required|exists:agent_packages,id',
            'customer_phone' => 'required|string|max:20',

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

        $customPricing = AgentPackagePricing::where('agent_id', $SubAgent->agent_id)
            ->where('package_id', $package->id)
            ->where('is_active', true)
            ->first();

        $price = (float) ($customPricing?->sub_agent_price ?? $package->price);

        if ($SubAgent->balance < $price) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Please top up your wallet.',
            ], 400);
        }

        $dealer = User::find($SubAgent->agent_id);

        $deducted = SubAgent::where('id', $SubAgent->id)
            ->where('balance', '>=', $price)
            ->decrement('balance', $price);

        if (!$deducted) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Please top up your wallet.',
            ], 400);
        }

        $SubAgent->refresh();
        $newBalance = (float) $SubAgent->balance;

        $dealerHasFunds = $dealer && (float) $dealer->balance >= $price;
        $orderStatus = 'failed';
        $failureReason = null;

        if ($dealerHasFunds) {
            $dealerDeducted = User::where('id', $dealer->id)
                ->where('balance', '>=', $price)
                ->decrement('balance', $price);

            if ($dealerDeducted) {
                $orderStatus = 'processing';
            } else {
                $failureReason = 'Failed, Not enough funds in dealers account';
            }
        } else {
            $failureReason = 'Failed, Not enough funds in dealers account';
        }

        $orderRef = 'SUB-' . strtoupper(Str::random(12));

        $order = StoreOrder::create([
            'order_ref' => $orderRef,
            'agent_id' => $SubAgent->agent_id,
            'sub_agent_id' => $SubAgent->id,
            'customer_phone' => $validated['customer_phone'],

            'package_id' => $package->id,
            'network_type' => $package->network_type,
            'package_size_gb' => $package->package_size_gb ?? ($package->package_size_mb / 1024),
            'sold_price' => $price,
            'payment_method' => 'sub_agent_wallet',
            'payment_status' => $orderStatus === 'processing' ? 'paid' : 'failed',
            'status' => $orderStatus,
            'failure_reason' => $failureReason,
        ]);

        SubAgentBalanceHistory::create([
            'sub_agent_id' => $SubAgent->id,
            'change_amount' => -$price,
            'balance_after' => $newBalance,
            'reason' => $orderStatus === 'processing' ? 'order_payment' : 'order_failed_dealer_funds',
            'ref_id' => $order->id,
            'description' => $orderStatus === 'processing'
                ? "Payment for {$package->package_name} to {$validated['customer_phone']}"
                : "Order failed - dealer insufficient funds for {$package->package_name}",
        ]);

        if ($orderStatus === 'processing') {
            \App\Models\BalanceHistory::create([
                'agent_id' => $dealer->id,
                'change_amount' => -$price,
                'balance_after' => (float) $dealer->balance,
                'reason' => 'sub_agent_order',
                'ref_id' => $order->id,
                'beneficiary_number' => $validated['customer_phone'],
                'description' => "Sub-Agent Order #{$order->id} to {$validated['customer_phone']}",
            ]);

            if (!$package->manual_fulfillment && app(\App\Services\DataBundle\DataBundleRouter::class)->isCredentialsConfigured()) {
                $capacityMb = app(\App\Services\DataBundle\DataBundleRouter::class)->convertPackageSize(
                    $package->package_size_gb ? ($package->package_size_gb . ' GB') : ($package->package_size_mb . ' MB')
                );
                $network = app(\App\Services\DataBundle\DataBundleRouter::class)->normalizeNetwork($validated['network_type']);

                $providerResult = app(\App\Services\DataBundle\DataBundleRouter::class)->purchaseData(
                    msisdn: $validated['customer_phone'],
                    network: $network,
                    capacityMb: $capacityMb,
                    paymentMethod: 'sub_agent_wallet',
                    localOrderId: $order->id,
                    submissionKey: 'subagent:' . $order->id,
                );

                if (empty($providerResult['success'])) {
                    $SubAgent->refresh();
                    $SubAgent->increment('balance', $price);
                    $refundedBalance = (float) $SubAgent->balance;

                    SubAgentBalanceHistory::create([
                        'sub_agent_id' => $SubAgent->id,
                        'change_amount' => $price,
                        'balance_after' => $refundedBalance,
                        'reason' => 'api_failure_refund',
                        'ref_id' => $order->id,
                        'description' => "Refund for failed order #{$order->id} - API Error",
                    ]);

                    $dealer->refresh();
                    $dealer->increment('balance', $price);

                    \App\Models\BalanceHistory::create([
                        'agent_id' => $dealer->id,
                        'change_amount' => $price,
                        'balance_after' => (float) $dealer->balance,
                        'reason' => 'api_failure_refund',
                        'ref_id' => $order->id,
                        'beneficiary_number' => $validated['customer_phone'],
                        'description' => "Refund for failed Sub-Agent order #{$order->id} - API Error",
                    ]);

                    $order->update([
                        'status' => 'failed',
                        'failure_reason' => $providerResult['error'] ?? 'API Error',
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => $providerResult['error'] ?? 'API submission failed',
                        'data' => [
                            'order_ref' => $orderRef,
                            'order_id' => $order->id,
                            'status' => 'failed',
                            'balance_after' => $refundedBalance,
                        ],
                    ]);
                } else {
                    $order->update([
                        'external_transaction_id' => $providerResult['external_transaction_id'] ?? null,
                        'external_reference' => $providerResult['external_reference'] ?? null,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => $orderStatus === 'processing',
            'message' => $orderStatus === 'processing'
                ? 'Order placed successfully!'
                : 'Order could not be processed due to dealer account issue.',
            'data' => [
                'order_ref' => $orderRef,
                'order_id' => $order->id,
                'status' => $orderStatus,
                'balance_after' => $orderStatus === 'processing' ? $newBalance : (float)$SubAgent->balance,
                'amount_deducted' => $price,
            ],
        ]);
    }

    public function index()
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent) {
            return redirect()->route('Sub-Agent.login');
        }

        $orders = StoreOrder::where('sub_agent_id', $SubAgent->id)
            ->latest()
            ->paginate(20)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'order_ref' => $order->order_ref,
                    'customer_phone' => $order->customer_phone,
                    'customer_name' => $order->customer_name,
                    'network_type' => $order->network_type,
                    'package_size_gb' => (float) $order->package_size_gb,
                    'sold_price' => (float) $order->sold_price,
                    'status' => $order->status,
                    'failure_reason' => $order->failure_reason,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('SubAgent/Orders', [
            'orders' => $orders,
        ]);
    }
}
