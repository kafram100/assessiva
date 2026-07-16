<?php

namespace App\Filament\Pages;

use App\Models\AgentOrder;
use App\Models\AgentPackage;
use App\Models\Order;
use App\Models\StoreOrder;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SalesAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static string $view = 'filament.pages.sales-analytics';

    protected static ?string $title = 'Sales Analytics';

    private function parseSizeToGB(string $size): float
    {
        $size = strtoupper(trim($size));
        if (str_contains($size, 'GB')) {
            return (float) str_replace('GB', '', $size);
        }
        if (str_contains($size, 'MB')) {
            return (float) str_replace('MB', '', $size) / 1024;
        }
        return 0;
    }

    private function calcProfit(Carbon $start, Carbon $end): array
    {
        $packages = AgentPackage::pluck('price', DB::raw("CONCAT(network_type, '|', package_size_gb)"));
        $getCost = function (string $network, string $size) use ($packages): float {
            $gb = $this->parseSizeToGB($size);
            return (float) ($packages[strtoupper($network) . '|' . $gb] ?? 0);
        };

        $profit = 0;
        $revenue = 0;
        $orderCount = 0;

        $storeOrders = StoreOrder::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('sold_price', 'base_cost', 'package_size_gb');
        foreach ($storeOrders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $profit += (float) $o->sold_price - (float) $o->base_cost;
            } else {
                $cost = (float) $o->package_size_gb * \App\Filament\Pages\Dashboard::BASE_PRICE_PER_GB;
                $profit += (float) $o->sold_price - $cost;
            }
            $revenue += (float) $o->sold_price;
            $orderCount++;
        }

        $agentOrders = AgentOrder::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('amount', 'network_type', 'package_size', 'base_cost');
        foreach ($agentOrders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $profit += (float) $o->amount - (float) $o->base_cost;
            } else {
                $cost = $getCost($o->network_type, $o->package_size);
                $profit += (float) $o->amount - $cost;
            }
            $revenue += (float) $o->amount;
            $orderCount++;
        }

        $orders = Order::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('amount', 'network_type', 'package_size', 'base_cost');
        foreach ($orders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $profit += (float) $o->amount - (float) $o->base_cost;
            } else {
                $cost = $getCost($o->network_type, $o->package_size);
                $profit += (float) $o->amount - $cost;
            }
            $revenue += (float) $o->amount;
            $orderCount++;
        }

        return [
            'profit' => round($profit, 2),
            'revenue' => round($revenue, 2),
            'orders' => $orderCount,
        ];
    }

    protected function getViewData(): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();
        $dayEnd = now()->endOfDay();

        $totalOrders = Order::count() + AgentOrder::count() + StoreOrder::count();
        $completedOrders = Order::whereIn('status', ['completed', 'delivered'])->count()
            + AgentOrder::whereIn('status', ['completed', 'delivered'])->count()
            + StoreOrder::whereIn('status', ['completed', 'delivered'])->count();
        $pendingOrders = Order::where('status', 'pending')->count()
            + AgentOrder::where('status', 'pending')->count()
            + StoreOrder::where('status', 'pending')->count();

        $totalAgents = User::where('role', 'agent')->count();
        $totalDealers = User::where('role', 'dealer')->count();
        $agentBalances = (float) User::where('role', 'agent')->sum('balance');
        $dealerBalances = (float) User::where('role', 'dealer')->sum('balance');

        $todayStats = $this->calcProfit($today, $dayEnd);
        $weekStats = $this->calcProfit($weekStart, $dayEnd);
        $monthStats = $this->calcProfit($monthStart, $dayEnd);
        $yearStats = $this->calcProfit($yearStart, $dayEnd);

        $dailyStats = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $stats = $this->calcProfit($date, (clone $date)->endOfDay());
            $dailyStats->push([
                'date' => $date->format('M d'),
                'revenue' => $stats['revenue'],
                'profit' => $stats['profit'],
                'orders' => $stats['orders'],
            ]);
        }

        $recentOrders = AgentOrder::with('agent')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'agent' => $o->agent?->username ?? 'Deleted',
                'package_size' => $o->package_size,
                'amount' => (float) $o->amount,
                'network_type' => $o->network_type,
                'status' => $o->status,
                'created_at' => $o->created_at?->format('M d, Y g:i A'),
            ]);

        $topAgents = User::whereIn('role', ['agent', 'dealer'])
            ->withSum(['agentOrders' => function ($q) {
                $q->whereIn('status', ['completed', 'delivered']);
            }], 'amount')
            ->having('agent_orders_sum_amount', '>', 0)
            ->orderByDesc('agent_orders_sum_amount')
            ->take(5)
            ->get()
            ->map(fn ($u) => [
                'username' => $u->username,
                'role' => $u->role,
                'total_sales' => (float) $u->agent_orders_sum_amount,
            ]);

        return [
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders,
            'pendingOrders' => $pendingOrders,
            'totalAgents' => $totalAgents,
            'totalDealers' => $totalDealers,
            'agentBalances' => $agentBalances,
            'dealerBalances' => $dealerBalances,
            'todayStats' => $todayStats,
            'weekStats' => $weekStats,
            'monthStats' => $monthStats,
            'yearStats' => $yearStats,
            'dailyStats' => $dailyStats,
            'recentOrders' => $recentOrders,
            'topAgents' => $topAgents,
        ];
    }
}
