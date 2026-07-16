<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentOrder;
use App\Models\AgentPackage;
use App\Models\BalanceHistory;
use App\Models\User;
use App\Services\DataBundle\DataBundleRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function packages(Request $request)
    {
        $user = $request->user();

        $packages = AgentPackage::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('role', $user->role)->orWhere('role', 'all');
            })
            ->where(function ($q) {
                $q->where('network_type', '!=', 'ALL')
                  ->orWhere('manual_fulfillment', true);
            })
            ->orderByRaw("CASE WHEN network_type = 'MTN' THEN 0 WHEN network_type = 'VODAFONE' THEN 1 WHEN network_type = 'AIRTELTIGO' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')
            ->get()
            ->map(function ($pkg) {
                return [
                    'id' => $pkg->id,
                    'network_type' => $pkg->network_type,
                    'package_name' => $pkg->package_name,
                    'package_size' => $pkg->package_size_gb
                        ? ($pkg->package_size_gb . ' GB')
                        : ($pkg->package_size_mb . ' MB'),
                    'price' => (float) $pkg->price,
                    'sort_order' => $pkg->sort_order,
                ];
            })
            ->groupBy('network_type');

        return response()->json([
            'success' => true,
            'data' => $packages,
            'networks' => $packages->keys(),
            'balance' => (float) $user->balance,
        ]);
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:agent_packages,id',
            'beneficiary_number' => 'required|string|max:20',
            'network_type' => 'required|string',
        ]);

        $user = $request->user();

        $package = AgentPackage::findOrFail($validated['package_id']);

        if (!in_array($package->role, ['all', 'dealer', $user->role])) {
            return response()->json([
                'success' => false,
                'message' => 'This package is not available for your role.',
            ], 403);
        }

        if (!app(DataBundleRouter::class)->isNetworkActive($package->network_type)) {
            return response()->json([
                'success' => false,
                'message' => "The {$package->network_type} network is currently unavailable. Please try again later.",
            ], 503);
        }

        if (!app(DataBundleRouter::class)->isValidNetworkPrefix($validated['beneficiary_number'], $package->network_type)) {
            return response()->json([
                'success' => false,
                'message' => "The phone number provided does not match the selected {$package->network_type} network.",
            ], 422);
        }

        $amount = (float) $package->price;

        $packageSize = $package->package_size_gb
            ? ($package->package_size_gb . ' GB')
            : ($package->package_size_mb . ' MB');

        $affected = User::where('id', $user->id)->where('balance', '>=', $amount)->decrement('balance', $amount);
        if ($affected === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Your balance is GHS ' . number_format($user->balance, 2) . ' but this order costs GHS ' . number_format($amount, 2) . '.',
            ], 400);
        }

        $user->refresh();
        $oldBalance = $user->balance + $amount;
        $newBalance = $user->balance;

        $agentOrder = AgentOrder::create([
            'agent_id' => $user->id,
            'package_id' => $package->id,
            'package_size' => $packageSize,
            'amount' => $amount,
            'beneficiary_number' => $validated['beneficiary_number'],
            'network_type' => $validated['network_type'],
            'status' => 'pending',
            'tracking_key' => strtoupper(Str::random(8)),
            'payment_method' => 'wallet',
        ]);

        BalanceHistory::create([
            'agent_id' => $user->id,
            'change_amount' => -$amount,
            'balance_after' => $newBalance,
            'reason' => 'order_purchase',
            'ref_id' => $agentOrder->id,
            'beneficiary_number' => $validated['beneficiary_number'],
            'description' => "Order #{$agentOrder->id} - {$packageSize} {$validated['network_type']} to {$validated['beneficiary_number']}",
        ]);

        $providerMessage = null;
        $providerSuccess = null;

        if ($package->manual_fulfillment) {
            $providerMessage = 'Manual fulfillment — no API submission';
        } elseif (app(DataBundleRouter::class)->isCredentialsConfigured()) {
            $capacityMb = app(DataBundleRouter::class)->convertPackageSize($packageSize);
            $network = app(DataBundleRouter::class)->normalizeNetwork($validated['network_type']);

            $providerResult = app(DataBundleRouter::class)->purchaseData(
                msisdn: $validated['beneficiary_number'],
                network: $network,
                capacityMb: $capacityMb,
                paymentMethod: 'agent_wallet',
                localOrderId: $agentOrder->id,
                submissionKey: 'agent:' . $agentOrder->id,
            );

            $providerSuccess = !empty($providerResult['success']);
            $providerMessage = $providerSuccess
                ? 'Data bundle submitted for delivery'
                : ($providerResult['error'] ?? 'Provider submission failed');

            $agentOrder->update([
                'status' => $providerSuccess ? 'processing' : 'failed',
                'base_cost' => $providerResult['base_cost'] ?? null,
                'external_transaction_id' => $providerResult['external_transaction_id'] ?? null,
                'external_reference' => $providerResult['external_reference'] ?? null,
                'api_response_data' => $providerResult['data'] ?? null,
            ]);

            if (!$providerSuccess) {
                $user->refresh();
                $user->increment('balance', $amount);
                $refundBalance = (float) $user->balance;

                BalanceHistory::create([
                    'agent_id' => $user->id,
                    'change_amount' => $amount,
                    'balance_after' => $refundBalance,
                    'reason' => 'api_failure_refund',
                    'ref_id' => $agentOrder->id,
                    'beneficiary_number' => $validated['beneficiary_number'],
                    'description' => "Refund for failed order #{$agentOrder->id} - API Error",
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $providerMessage,
                    'data' => [
                        'order' => $agentOrder,
                        'balance_after' => $refundBalance,
                        'amount_deducted' => 0,
                    ],
                ]);
            }
        } else {
            $providerMessage = app(DataBundleRouter::class)->getMissingCredentialsMessage();
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'data' => [
                'order' => $agentOrder,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'amount_deducted' => $amount,
                'provider_status' => $providerSuccess ?? null,
                'provider_message' => $providerMessage,
            ],
        ]);
    }
}
