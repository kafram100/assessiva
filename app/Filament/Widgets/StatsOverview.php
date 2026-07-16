<?php

namespace App\Filament\Widgets;

use App\Models\AgentOrder;
use App\Models\Order;
use App\Models\SubAgent;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public function getStats(): array
    {
        $totalData = Order::whereIn('status', ['completed', 'delivered'])->sum('package_size');
        $agentData = AgentOrder::whereIn('status', ['completed', 'delivered'])->sum('package_size');

        $totalAgents = User::where('role', 'agent')->count();
        $SubAgents = SubAgent::count();
        $agentBalances = User::where('role', 'agent')->sum('balance');

        $todayUsage = AgentOrder::whereDate('created_at', today())->sum('amount');
        $todayDebitCount = AgentOrder::whereDate('created_at', today())->count();
        $SubAgentBalances = SubAgent::sum('balance');

        $pendingOrders = AgentOrder::where('status', 'pending')->count();
        $verifiedOrders = AgentOrder::where('status', 'verified')->count();
        $completedOrders = AgentOrder::whereIn('status', ['completed', 'delivered'])->count();

        return [
            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting verification')
                ->color('warning'),

            Stat::make('Verified Orders', $verifiedOrders)
                ->description('Ready for processing')
                ->color('info'),

            Stat::make('Completed Orders', $completedOrders)
                ->description('Successfully delivered')
                ->color('success'),

            Stat::make('Total Data Delivered', number_format($totalData + $agentData, 2) . ' GB')
                ->description('Regular: ' . number_format($totalData, 2) . ' GB | Agent: ' . number_format($agentData, 2) . ' GB')
                ->color('primary'),

            Stat::make('Total Agents', $totalAgents)
                ->description("Sub Agents: {$SubAgents}")
                ->color('primary'),

            Stat::make('Agent Balances', 'GHS ' . number_format($agentBalances, 2))
                ->description('Combined balance of all agents')
                ->color('success'),

            Stat::make("Today's Usage", 'GHS ' . number_format($todayUsage, 2))
                ->description("{$todayDebitCount} debit records today")
                ->color('warning'),

            Stat::make('Sub Agents Balance', 'GHS ' . number_format($SubAgentBalances, 2))
                ->description('Combined balance of all Sub Agents')
                ->color('info'),
        ];
    }
}
