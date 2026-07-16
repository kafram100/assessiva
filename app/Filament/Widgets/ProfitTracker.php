<?php

namespace App\Filament\Widgets;

use App\Models\AgentOrder;
use App\Models\StoreOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitTracker extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $today = today();

        $dailyOrders = StoreOrder::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->select('sold_price', 'base_cost', 'package_size_gb')
            ->get();

        $dailyProfit = $dailyOrders->sum(fn ($o) =>
            $o->base_cost !== null
                ? (float) $o->sold_price - (float) $o->base_cost
                : (float) $o->sold_price - ((float) $o->package_size_gb * \App\Filament\Pages\Dashboard::BASE_PRICE_PER_GB)
        );

        $dailyRevenue = $dailyOrders->sum('sold_price');

        $allOrders = StoreOrder::where('status', 'completed')->select('sold_price', 'base_cost', 'package_size_gb')->get();

        $totalProfit = $allOrders->sum(fn ($o) =>
            $o->base_cost !== null
                ? (float) $o->sold_price - (float) $o->base_cost
                : (float) $o->sold_price - ((float) $o->package_size_gb * \App\Filament\Pages\Dashboard::BASE_PRICE_PER_GB)
        );

        $totalRevenue = $allOrders->sum('sold_price');

        return [
            Stat::make('Today\'s Profit', 'GHS ' . number_format($dailyProfit, 2))
                ->description('From store orders')
                ->color('success'),

            Stat::make('Total Profit', 'GHS ' . number_format($totalProfit, 2))
                ->description('All time store profit')
                ->color('primary'),

            Stat::make('Today\'s Revenue', 'GHS ' . number_format($dailyRevenue, 2))
                ->description('Total sales today')
                ->color('info'),

            Stat::make('Total Revenue', 'GHS ' . number_format($totalRevenue, 2))
                ->description('All time store revenue')
                ->color('warning'),
        ];
    }
}
