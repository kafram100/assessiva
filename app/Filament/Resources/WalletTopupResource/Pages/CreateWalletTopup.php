<?php

namespace App\Filament\Resources\WalletTopupResource\Pages;

use App\Filament\Resources\WalletTopupResource;
use App\Mail\WalletTopupMail;
use App\Models\BalanceHistory;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateWalletTopup extends CreateRecord
{
    protected static string $resource = WalletTopupResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        $user = User::find($record->account_id);
        if (!$user) {
            return;
        }

        $amount = (float) $record->total_amount;

        $oldBalance = $user->balance;
        $user->increment('balance', $amount);
        $newBalance = $user->balance;

        BalanceHistory::create([
            'agent_id' => $user->id,
            'change_amount' => $amount,
            'balance_after' => $newBalance,
            'reason' => 'topup',
            'description' => "Wallet top-up via {$record->provider} - Ref: {$record->reference}",
        ]);

        $record->update(['credited_at' => now()]);

        try {
            Mail::to($user->email)->send(new WalletTopupMail($record, $user, (float) $newBalance));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
