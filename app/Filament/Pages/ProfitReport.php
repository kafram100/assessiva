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

class ProfitReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.profit-report';

    protected static ?string $title = 'Profit Report';

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

    private function calcProfitByType(Carbon $start, Carbon $end): array
    {
        $packages = AgentPackage::pluck('price', DB::raw("CONCAT(network_type, '|', package_size_gb)"));
        $getCost = function (string $network, string $size) use ($packages): float {
            $gb = $this->parseSizeToGB($size);
            return (float) ($packages[strtoupper($network) . '|' . $gb] ?? 0);
        };

        $storeRevenue = 0;
        $storeProfit = 0;
        $storeCount = 0;

        $storeOrders = StoreOrder::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('sold_price', 'base_cost', 'package_size_gb');
        foreach ($storeOrders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $storeProfit += (float) $o->sold_price - (float) $o->base_cost;
            } else {
                $cost = (float) $o->package_size_gb * \App\Filament\Pages\Dashboard::BASE_PRICE_PER_GB;
                $storeProfit += (float) $o->sold_price - $cost;
            }
            $storeRevenue += (float) $o->sold_price;
            $storeCount++;
        }

        $agentRevenue = 0;
        $agentProfit = 0;
        $agentCount = 0;

        $agentOrders = AgentOrder::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('amount', 'network_type', 'package_size', 'base_cost');
        foreach ($agentOrders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $agentProfit += (float) $o->amount - (float) $o->base_cost;
            } else {
                $cost = $getCost($o->network_type, $o->package_size);
                $agentProfit += (float) $o->amount - $cost;
            }
            $agentRevenue += (float) $o->amount;
            $agentCount++;
        }

        $publicRevenue = 0;
        $publicProfit = 0;
        $publicCount = 0;

        $orders = Order::whereIn('status', ['completed', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->select('amount', 'network_type', 'package_size', 'base_cost');
        foreach ($orders->cursor() as $o) {
            if ($o->base_cost !== null) {
                $publicProfit += (float) $o->amount - (float) $o->base_cost;
            } else {
                $cost = $getCost($o->network_type, $o->package_size);
                $publicProfit += (float) $o->amount - $cost;
            }
            $publicRevenue += (float) $o->amount;
            $publicCount++;
        }

        return [
            'store' => ['revenue' => round($storeRevenue, 2), 'profit' => round($storeProfit, 2), 'orders' => $storeCount],
            'agent' => ['revenue' => round($agentRevenue, 2), 'profit' => round($agentProfit, 2), 'orders' => $agentCount],
            'public' => ['revenue' => round($publicRevenue, 2), 'profit' => round($publicProfit, 2), 'orders' => $publicCount],
        ];
    }

    protected function getViewData(): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();
        $dayEnd = now()->endOfDay();

        $todayByType = $this->calcProfitByType($today, $dayEnd);
        $weekByType = $this->calcProfitByType($weekStart, $dayEnd);
        $monthByType = $this->calcProfitByType($monthStart, $dayEnd);
        $yearByType = $this->calcProfitByType($yearStart, $dayEnd);

        $allTimeStart = Carbon::createFromDate(2020, 1, 1);
        $allTime = $this->calcProfitByType($allTimeStart, $dayEnd);

        $monthlyProfits = collect();
        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            $stats = $this->calcProfitByType($start, $end);
            $totalProfit = $stats['store']['profit'] + $stats['agent']['profit'] + $stats['public']['profit'];
            $monthlyProfits->push([
                'label' => $start->format('M Y'),
                'profit' => $totalProfit,
                'store' => $stats['store']['profit'],
                'agent' => $stats['agent']['profit'],
                'public' => $stats['public']['profit'],
            ]);
        }

        $agentRankings = User::whereIn('role', ['agent', 'dealer'])
            ->withSum(['agentOrders' => fn ($q) => $q->whereIn('status', ['completed', 'delivered'])], 'amount')
            ->having('agent_orders_sum_amount', '>', 0)
            ->orderByDesc('agent_orders_sum_amount')
            ->take(20)
            ->get()
            ->map(fn ($u) => [
                'username' => $u->username,
                'role' => $u->role,
                'total_sales' => (float) $u->agent_orders_sum_amount,
                'order_count' => $u->agentOrders()->whereIn('status', ['completed', 'delivered'])->count(),
            ]);

        return [
            'todayByType' => $todayByType,
            'weekByType' => $weekByType,
            'monthByType' => $monthByType,
            'yearByType' => $yearByType,
            'allTime' => $allTime,
            'monthlyProfits' => $monthlyProfits,
            'agentRankings' => $agentRankings,
        ];
    }
}
