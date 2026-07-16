<?php

namespace App\Filament\Resources\WalletTopupResource\Pages;

use App\Filament\Resources\WalletTopupResource;
use App\Mail\WalletTopupMail;
use App\Models\BalanceHistory;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditWalletTopup extends EditRecord
{
    protected static string $resource = WalletTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->status !== 'completed' || $record->credited_at) {
            return;
        }

        $user = User::find($record->account_id);
        if (!$user) {
            return;
        }

        $amount = (float) $record->total_amount;

        $user->increment('balance', $amount);
        $newBalance = (float) $user->balance;

        BalanceHistory::create([
            'agent_id' => $user->id,
            'change_amount' => $amount,
            'balance_after' => $newBalance,
            'reason' => 'topup',
            'description' => "Wallet top-up via {$record->provider} - Ref: {$record->reference}",
        ]);

        $record->update(['credited_at' => now()]);

        try {
            Mail::to($user->email)->send(new WalletTopupMail($record, $user, $newBalance));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
