<?php

namespace App\Filament\Widgets;

use App\Models\AgentOrder;
use App\Models\StoreOrder;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrderChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = 30;
        $labels = [];
        $agentCounts = [];
        $storeCounts = [];
        $revenues = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');

            $agentCounts[] = AgentOrder::whereDate('created_at', $date)->count();
            $storeCounts[] = StoreOrder::whereDate('created_at', $date)->count();
            $revenues[] = (float) StoreOrder::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('sold_price');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Agent Orders',
                    'data' => $agentCounts,
                    'backgroundColor' => '#0F9F95',
                    'borderColor' => '#0F9F95',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Store Orders',
                    'data' => $storeCounts,
                    'backgroundColor' => '#F59E0B',
                    'borderColor' => '#F59E0B',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string
    {
        return 'Orders (Last 30 Days)';
    }
}
