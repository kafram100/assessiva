<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\PaystackGateway;
use App\Services\Payment\PaymentRouter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $gateway = app(PaymentRouter::class)->forContext(PaymentRouter::CONTEXT_WALLET_TOPUP);
        $feeRate = $gateway instanceof PaystackGateway ? 0.02 : 0;

        $topups = $user->walletTopups()
            ->latest()
            ->paginate(15)
            ->through(function ($topup) {
                return [
                    'id' => $topup->id,
                    'reference' => $topup->reference,
                    'base_amount' => (float) $topup->base_amount,
                    'processing_fee' => (float) $topup->processing_fee,
                    'total_amount' => (float) $topup->total_amount,
                    'provider' => $topup->provider,
                    'status' => $topup->status,
                    'credited_at' => $topup->credited_at?->format('Y-m-d H:i'),
                    'paid_at' => $topup->paid_at?->format('Y-m-d H:i'),
                    'created_at' => $topup->created_at?->format('Y-m-d H:i'),
                ];
            });

        $balanceHistory = $user->balanceHistory()
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'change_amount' => (float) $entry->change_amount,
                    'balance_after' => (float) $entry->balance_after,
                    'reason' => $entry->reason,
                    'description' => $entry->description,
                    'beneficiary_number' => $entry->beneficiary_number,
                    'created_at' => $entry->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Agent/Wallet', [
            'balance' => (float) $user->balance,
            'feeRate' => $feeRate,
            'topups' => $topups,
            'balanceHistory' => $balanceHistory,
        ]);
    }
}
