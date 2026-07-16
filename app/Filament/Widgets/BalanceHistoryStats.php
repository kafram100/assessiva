<?php

namespace App\Filament\Widgets;

use App\Models\BalanceHistory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BalanceHistoryStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = today();

        $todayCredits = BalanceHistory::whereDate('created_at', $today)
            ->where('change_amount', '>', 0)
            ->sum('change_amount');

        $todayDebits = BalanceHistory::whereDate('created_at', $today)
            ->where('change_amount', '<', 0)
            ->sum('change_amount');

        $todayCount = BalanceHistory::whereDate('created_at', $today)->count();

        return [
            Stat::make("Today's Activity", $todayCount . ' entry(ies)')
                ->description('Total balance changes today')
                ->color('primary'),
            Stat::make("Today's Credits", 'GHS ' . number_format($todayCredits, 2))
                ->description('Amount added today')
                ->color('success'),
            Stat::make("Today's Debits", 'GHS ' . number_format(abs($todayDebits), 2))
                ->description('Amount deducted today')
                ->color('danger'),
        ];
    }
}
