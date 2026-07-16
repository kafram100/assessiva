<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $recentOrders = $user->agentOrders()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'package_size' => $order->package_size,
                    'amount' => $order->amount,
                    'beneficiary_number' => $order->beneficiary_number,
                    'network_type' => $order->network_type,
                    'status' => $order->status,
                    'tracking_key' => $order->tracking_key,
                    'created_at' => $order->created_at?->diffForHumans(),
                ];
            });

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

        $totalOrders = $user->agentOrders()->count();
        $successfulOrders = $user->agentOrders()->where('status', 'completed')->count();
        $pendingOrders = $user->agentOrders()->where('status', 'pending')->count();
        $failedOrders = $user->agentOrders()->whereIn('status', ['failed', 'cancelled'])->count();

        return Inertia::render('Agent/Dashboard', [
            'stats' => [
                'balance' => (float) $user->balance,
                'total_orders' => $totalOrders,
                'successful_orders' => $successfulOrders,
                'pending_orders' => $pendingOrders,
                'failed_orders' => $failedOrders,
            ],
            'recentOrders' => $recentOrders,
            'recentBalanceHistory' => $recentBalanceHistory,
        ]);
    }
}
