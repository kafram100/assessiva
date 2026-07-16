<?php

namespace App\Filament\Pages;

use App\Models\AgentOrder;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AgentMonthlyOrders extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.agent-monthly-orders';

    protected static ?string $title = 'Agent Monthly Orders';

    public ?int $agentId = null;
    public ?int $year = null;

    public function mount(): void
    {
        $this->agentId = request('agentId') ? (int) request('agentId') : null;
        $this->year = request('year') ? (int) request('year') : null;
    }

    protected function getViewData(): array
    {
        $year = $this->year ?? now()->year;

        $query = AgentOrder::toBase()
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('agent_id'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw("SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) as completed_orders"),
                DB::raw("SUM(CASE WHEN status IN ('completed','delivered') THEN amount ELSE 0 END) as completed_amount"),
                DB::raw("SUM(CASE WHEN status NOT IN ('completed','delivered') THEN 1 ELSE 0 END) as failed_orders")
            )
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'), 'agent_id')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('total_amount', 'desc');

        if ($this->agentId) {
            $query->where('agent_id', $this->agentId);
        }

        $rows = $query->get();

        $monthlyOrders = collect();
        foreach ($rows as $row) {
            $user = User::find($row->agent_id);
            $monthlyOrders->push([
                'year' => $row->year,
                'month' => Carbon::createFromDate($row->year, $row->month, 1)->format('F'),
                'month_num' => $row->month,
                'user' => $user?->username ?? 'Deleted',
                'role' => $user?->role ?? 'unknown',
                'total_orders' => $row->total_orders,
                'completed_orders' => $row->completed_orders,
                'failed_orders' => $row->failed_orders,
                'total_amount' => (float) $row->total_amount,
                'completed_amount' => (float) $row->completed_amount,
            ]);
        }

        $summary = AgentOrder::toBase()
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw("SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) as completed_orders"),
                DB::raw("SUM(CASE WHEN status IN ('completed','delivered') THEN amount ELSE 0 END) as completed_amount")
            )
            ->first();

        $agents = User::whereIn('role', ['agent', 'dealer'])->orderBy('username')->pluck('username', 'id');

        $years = AgentOrder::select(DB::raw('YEAR(created_at) as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return [
            'monthlyOrders' => $monthlyOrders,
            'summary' => $summary,
            'agents' => $agents,
            'years' => $years,
            'selectedYear' => $year,
        ];
    }
}
