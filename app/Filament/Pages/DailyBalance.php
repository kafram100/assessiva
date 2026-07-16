<?php

namespace App\Filament\Pages;

use App\Models\BalanceHistory;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class DailyBalance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.daily-balance';

    protected static ?string $title = 'Daily Balance';

    public ?int $userId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->userId = request('userId') ? (int) request('userId') : null;
        $this->dateFrom = request('dateFrom') ?: null;
        $this->dateTo = request('dateTo') ?: null;
    }

    protected function getViewData(): array
    {
        $query = BalanceHistory::toBase()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('agent_id'),
                DB::raw('SUM(CASE WHEN change_amount >= 0 THEN change_amount ELSE 0 END) as total_credit'),
                DB::raw('SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END) as total_debit'),
                DB::raw('COUNT(*) as tx_count')
            )
            ->whereNotNull('created_at')
            ->groupBy(DB::raw('DATE(created_at)'), 'agent_id')
            ->orderBy('date', 'desc');

        if ($this->userId) {
            $query->where('agent_id', $this->userId);
        }
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $rows = $query->get();

        $dailyBalances = collect();
        foreach ($rows as $row) {
            if (!$row->date) continue;
            $firstTx = BalanceHistory::where('agent_id', $row->agent_id)
                ->whereDate('created_at', $row->date)
                ->orderBy('created_at')
                ->first();
            $lastTx = BalanceHistory::where('agent_id', $row->agent_id)
                ->whereDate('created_at', $row->date)
                ->orderByDesc('created_at')
                ->first();
            $prevTx = BalanceHistory::where('agent_id', $row->agent_id)
                ->whereDate('created_at', '<', $row->date)
                ->orderByDesc('created_at')
                ->first();

            $user = User::find($row->agent_id);

            $openingBalance = (float) ($prevTx?->balance_after ?? $firstTx?->balance_after ?? 0);
            $closingBalance = (float) ($lastTx?->balance_after ?? 0);

            $dailyBalances->push([
                'date' => Carbon::parse($row->date)->format('M d, Y'),
                'user' => $user?->username ?? 'Deleted',
                'role' => $user?->role ?? 'unknown',
                'opening' => $openingBalance,
                'credit' => (float) $row->total_credit,
                'debit' => (float) $row->total_debit,
                'closing' => $closingBalance,
                'change' => round($closingBalance - $openingBalance, 2),
                'tx_count' => $row->tx_count,
            ]);
        }

        $agents = User::whereIn('role', ['agent', 'dealer'])->orderBy('username')->pluck('username', 'id');

        return [
            'dailyBalances' => $dailyBalances,
            'agents' => $agents,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $data = $this->getViewData()['dailyBalances'];
                    $csv = "Date,User,Role,Opening,Credit,Debit,Closing,Change,Transactions\n";
                    foreach ($data as $row) {
                        $csv .= "\"{$row['date']}\",{$row['user']},{$row['role']},{$row['opening']},{$row['credit']},{$row['debit']},{$row['closing']},{$row['change']},{$row['tx_count']}\n";
                    }
                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'daily-balance-' . now()->format('Y-m-d') . '.csv');
                }),
        ];
    }
}
