<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTopup;
use App\Models\BalanceHistory;
use App\Models\PaymentVerification;
use App\Mail\WalletTopupMail;
use App\Services\Payment\Gateways\PaystackGateway;
use App\Services\Payment\PaymentRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WalletTopupController extends Controller
{
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
        ]);

        $user = $request->user();
        $amount = (float) $validated['amount'];

        $gateway = app(PaymentRouter::class)->forContext(PaymentRouter::CONTEXT_WALLET_TOPUP);

        $isPaystack = $gateway instanceof PaystackGateway;

        $rate = $isPaystack ? 0.02 : 0;
        $fee = round($amount * $rate, 2);
        $total = round($amount + $fee, 2);
        $reference = 'WAL-' . strtoupper(Str::random(16));

        $topup = WalletTopup::create([
            'reference' => $reference,
            'account_type' => $user->role ?? 'agent',
            'account_id' => $user->id,
            'email' => $user->email,
            'base_amount' => $amount,
            'processing_fee' => $fee,
            'total_amount' => $total,
            'provider' => $isPaystack ? 'paystack' : 'moolre',
            'status' => 'pending',
        ]);

        $result = $gateway->initiate([
            'amount' => $total,
            'reference' => $reference,
            'email' => $user->email,
            'checkout' => !$isPaystack,
            'callback_url' => $isPaystack ? route('wallet.topup.paystack.callback') : route('wallet.topup.moolre.callback'),
            'redirect_url' => $isPaystack ? null : route('wallet.topup.moolre.redirect'),
            'metadata' => [
                'topup_id' => $topup->id,
                'user_id' => $user->id,
            ],
        ]);

        if (!$result['success']) {
            $topup->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate top-up',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $reference,
                'authorization_url' => $result['authorization_url'] ?? null,
                'transaction_id' => $result['transaction_id'] ?? null,
                'message' => $result['authorization_url'] ? 'Redirecting to payment page...' : 'Check your phone to approve the payment',
            ],
        ]);
    }

    public function status(Request $request, $reference)
    {
        $topup = WalletTopup::where('reference', $reference)->first();

        if (!$topup) {
            return response()->json([
                'success' => false,
                'message' => 'Top-up not found',
            ]);
        }

        $gateway = app(PaymentRouter::class)->gateway($topup->provider ?? 'moolre');
        $result = $gateway->verify($reference);

        if ($result['status'] === 'completed') {
            $this->creditTopup($topup, 'Moolre');
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment successful! Wallet credited.',
            ]);
        }

        if ($result['status'] === 'failed') {
            $topup->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'Payment failed. Please try again.',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'message' => 'Waiting for payment approval on your phone...',
        ]);
    }

    public function moolreCallback(Request $request)
    {
        $reference = $request->input('externalref')
            ?? $request->input('data.externalref')
            ?? $request->input('reference');

        if (!$reference) {
            return response()->json(['status' => 'error', 'message' => 'No reference'], 400);
        }

        $topup = WalletTopup::where('reference', $reference)->first();
        if (!$topup) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $gateway = app(PaymentRouter::class)->gateway($topup->provider ?? 'moolre');
        $result = $gateway->verify($reference);

        if ($result['status'] === 'completed' && $topup->status !== 'completed') {
            $this->creditTopup($topup, 'Moolre');
        }

        return response()->json(['status' => 'ok']);
    }

    private function walletPath(Request $request): string
    {
        $user = $request->user();
        if ($user && $user->role === 'dealer') {
            return '/dealer/wallet';
        }
        return '/agent/wallet';
    }

    public function moolreRedirect(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $wallet = $this->walletPath($request);

        $topup = WalletTopup::where('account_id', $user->id)
            ->where('provider', 'moolre')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$topup) {
            return redirect()->to($wallet . '?topup=failed&error=no_pending_topup');
        }

        $gateway = app(PaymentRouter::class)->gateway('moolre');
        $result = $gateway->verify($topup->reference);

        if ($result['status'] !== 'completed') {
            return redirect()->to($wallet . '?topup=failed');
        }

        if ($topup->status === 'completed') {
            return redirect()->to($wallet . '?topup=success');
        }

        $this->creditTopup($topup, 'Moolre');
        return redirect()->to($wallet . '?topup=success');
    }

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        $wallet = $this->walletPath($request);

        if (!$reference) {
            return redirect()->to($wallet . '?topup=failed&error=no_reference');
        }

        $gateway = app(PaymentRouter::class)->gateway('paystack');
        $result = $gateway->verify($reference);

        if (!$result['success']) {
            WalletTopup::where('reference', $reference)->update(['status' => 'failed']);
            return redirect()->to($wallet . '?topup=failed');
        }

        $topup = WalletTopup::where('reference', $reference)->first();
        if (!$topup) {
            return redirect()->to($wallet . '?topup=failed&error=topup_not_found');
        }

        $user = User::find($topup->account_id);
        if (!$user) {
            return redirect()->to($wallet . '?topup=failed&error=user_not_found');
        }

        $this->creditTopup($topup, 'Paystack');
        return redirect()->to($wallet . '?topup=success');
    }

    public function verifyTopup(Request $request, string $reference)
    {
        $user = $request->user();

        $topup = WalletTopup::where('reference', $reference)
            ->where('account_id', $user->id)
            ->first();

        if (!$topup) {
            return response()->json([
                'success' => false,
                'message' => 'Top-up not found.',
            ]);
        }

        if ($topup->status === 'completed') {
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment was already verified and credited.',
            ]);
        }

        if ($topup->status === 'failed') {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'This payment failed. Please initiate a new top-up.',
            ]);
        }

        $gateway = app(PaymentRouter::class)->gateway($topup->provider ?? 'moolre');
        $result = $gateway->verify($reference);

        if (($result['status'] ?? null) === 'completed' || ($result['success'] ?? false)) {
            $this->creditTopup($topup, ucfirst($topup->provider ?? 'Unknown'));
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment verified! Wallet has been credited.',
            ]);
        }

        if (($result['status'] ?? null) === 'failed') {
            $topup->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'Payment failed on the gateway. Please try again.',
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 'pending',
            'message' => 'Payment is still pending. Please wait a moment and try again.',
        ]);
    }

    public function verifyPending(Request $request)
    {
        $user = $request->user();

        $pendingTopups = WalletTopup::where('account_id', $user->id)
            ->where('status', 'pending')
            ->get();

        if ($pendingTopups->isEmpty()) {
            return response()->json([
                'success' => true,
                'verified' => 0,
                'total' => 0,
                'message' => 'No pending payments found.',
                'results' => [],
            ]);
        }

        $results = [];
        $router = app(PaymentRouter::class);

        foreach ($pendingTopups as $topup) {
            try {
                $gateway = $router->gateway($topup->provider);
                $result = $gateway->verify($topup->reference);

                if (($result['status'] ?? null) === 'completed' || ($result['success'] ?? false)) {
                    $this->creditTopup($topup, ucfirst($topup->provider ?? 'Unknown'));
                    $results[] = [
                        'reference' => $topup->reference,
                        'status' => 'completed',
                        'base_amount' => (float) $topup->base_amount,
                    ];
                } elseif (($result['status'] ?? null) === 'failed') {
                    $topup->update(['status' => 'failed']);
                    $results[] = [
                        'reference' => $topup->reference,
                        'status' => 'failed',
                    ];
                } else {
                    $results[] = [
                        'reference' => $topup->reference,
                        'status' => 'pending',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Auto-verify failed for {ref}: {msg}', [
                    'ref' => $topup->reference,
                    'msg' => $e->getMessage(),
                ]);
                $results[] = [
                    'reference' => $topup->reference,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $verified = count(array_filter($results, fn($r) => $r['status'] === 'completed'));

        return response()->json([
            'success' => true,
            'verified' => $verified,
            'total' => count($results),
            'message' => $verified > 0
                ? "{$verified} payment(s) verified and credited successfully."
                : 'No payments could be verified.',
            'results' => $results,
        ]);
    }

    private function creditTopup(WalletTopup $topup, string $provider): void
    {
        if ($topup->status === 'completed') return;

        $user = User::find($topup->account_id);
        if (!$user) return;

        $user->increment('balance', $topup->base_amount);
        $newBalance = (float) $user->balance;

        $topup->update([
            'status' => 'completed',
            'paid_at' => now(),
            'credited_at' => now(),
        ]);

        BalanceHistory::create([
            'agent_id' => $user->id,
            'change_amount' => $topup->base_amount,
            'balance_after' => $newBalance,
            'reason' => 'topup',
            'description' => "Wallet top-up via {$provider}",
            'ref_id' => $topup->id,
        ]);

        PaymentVerification::firstOrCreate(
            ['reference' => $topup->reference],
            [
                'gateway' => strtolower($provider),
                'user_type' => $topup->account_type,
                'user_id' => $user->id,
                'amount' => $topup->base_amount,
                'verified_at' => now(),
            ]
        );

        try {
            Mail::to($user->email)->send(new WalletTopupMail($topup, $user, $newBalance));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
