<?php

namespace App\Filament\Widgets;

use App\Models\WalletTopup;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletTopupStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = today();

        $todayTotal = WalletTopup::whereDate('created_at', $today)->sum('total_amount');
        $todayCount = WalletTopup::whereDate('created_at', $today)->count();
        $todayAdmin = WalletTopup::whereDate('created_at', $today)->where('provider', 'admin')->sum('total_amount');

        $monthTotal = WalletTopup::whereMonth('created_at', now()->month)->sum('total_amount');

        $allTime = WalletTopup::sum('total_amount');

        return [
            Stat::make("Today's Top-ups", 'GHS ' . number_format($todayTotal, 2))
                ->description("{$todayCount} top-up(s) today")
                ->color('success'),
            Stat::make("Today's Admin Credits", 'GHS ' . number_format($todayAdmin, 2))
                ->description('Credits from admin')
                ->color('info'),
            Stat::make('This Month', 'GHS ' . number_format($monthTotal, 2))
                ->description('Current month total')
                ->color('warning'),
            Stat::make('All Time', 'GHS ' . number_format($allTime, 2))
                ->description('Total top-ups ever')
                ->color('primary'),
        ];
    }
}
