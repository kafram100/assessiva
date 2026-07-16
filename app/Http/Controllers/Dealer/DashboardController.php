<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $agentOrders = $user->agentOrders()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'agent_' . $order->id,
                    'package_size' => $order->package_size,
                    'amount' => $order->amount,
                    'beneficiary_number' => $order->beneficiary_number,
                    'network_type' => $order->network_type,
                    'status' => $order->status,
                    'tracking_key' => $order->tracking_key,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'created_at_human' => $order->created_at?->diffForHumans(),
                    'type' => 'Agent',
                ];
            })->toArray();

        $storeOrders = $user->storeOrders()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'store_' . $order->id,
                    'package_size' => ((float) $order->package_size_gb) . ' GB',
                    'amount' => (float) $order->sold_price,
                    'beneficiary_number' => $order->customer_phone,
                    'network_type' => $order->network_type,
                    'status' => $order->status,
                    'tracking_key' => $order->tracking_id ?? '-',
                    'created_at' => $order->created_at?->toIso8601String(),
                    'created_at_human' => $order->created_at?->diffForHumans(),
                    'type' => $order->sub_agent_id ? 'Sub-Agent' : 'Storefront',
                ];
            })->toArray();

        $mergedOrders = collect(array_merge($agentOrders, $storeOrders))
            ->sortByDesc(fn ($o) => $o['created_at'])
            ->take(10)
            ->values()
            ->all();

        $recentBalanceHistory = $user->balanceHistory()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'change_amount' => (float) $entry->change_amount,
                    'balance_after' => (float) $entry->balance_after,
                    'reason' => $entry->reason,
                    'description' => $entry->description,
                    'created_at' => $entry->created_at?->diffForHumans(),
                ];
            });

        $totalAgentOrders = $user->agentOrders()->count();
        $successfulAgentOrders = $user->agentOrders()->whereIn('status', ['completed', 'delivered'])->count();
        $pendingAgentOrders = $user->agentOrders()->where('status', 'pending')->count();
        $failedAgentOrders = $user->agentOrders()->whereIn('status', ['failed', 'cancelled'])->count();

        $totalStoreOrders = $user->storeOrders()->count();
        $successfulStoreOrders = $user->storeOrders()->whereIn('status', ['completed', 'delivered'])->count();
        $pendingStoreOrders = $user->storeOrders()->where('status', 'pending')->count();
        $failedStoreOrders = $user->storeOrders()->whereIn('status', ['failed', 'cancelled'])->count();

        $SubAgentCount = $user->SubAgents()->count();

        return Inertia::render('Dealer/Dashboard', [
            'stats' => [
                'balance' => (float) $user->balance,
                'total_orders' => $totalAgentOrders + $totalStoreOrders,
                'successful_orders' => $successfulAgentOrders + $successfulStoreOrders,
                'pending_orders' => $pendingAgentOrders + $pendingStoreOrders,
                'failed_orders' => $failedAgentOrders + $failedStoreOrders,
                'sub_agent_count' => $SubAgentCount,
                'store_order_count' => $totalStoreOrders,
            ],
            'recentOrders' => $mergedOrders,
            'recentBalanceHistory' => $recentBalanceHistory,
        ]);
    }
}
