<?php

namespace App\Http\Controllers\SubAgent;

use App\Http\Controllers\Controller;
use App\Models\SubAgent;
use App\Models\SubAgentBalanceHistory;
use App\Models\WalletTopup;
use App\Services\Payment\Gateways\PaystackGateway;
use App\Services\Payment\PaymentRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TopupController extends Controller
{
    public function showForm()
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent) {
            return redirect()->route('Sub-Agent.login');
        }

        $topups = WalletTopup::where('account_type', 'sub_agent')
            ->where('account_id', $SubAgent->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'base_amount' => (float) $t->base_amount,
                'processing_fee' => (float) $t->processing_fee,
                'total_amount' => (float) $t->total_amount,
                'provider' => $t->provider,
                'status' => $t->status,
                'created_at' => $t->created_at->format('d M Y, H:i'),
            ]);

        return Inertia::render('SubAgent/Topup', [
            'balance' => (float) $SubAgent->balance,
            'topups' => ['data' => $topups],
        ]);
    }

    public function initiate(Request $request)
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent || !$SubAgent->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or account suspended.',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
        ]);

        $amount = (float) $validated['amount'];
        $reference = 'SUB-TOP-' . strtoupper(Str::random(16));

        $router = app(PaymentRouter::class);
        $gateway = $router->forDealerStore($SubAgent->agent_id);

        $isPaystack = $gateway instanceof PaystackGateway;
        $rate = $isPaystack ? 0.02 : 0;
        $fee = round($amount * $rate, 2);
        $total = round($amount + $fee, 2);

        $topup = WalletTopup::create([
            'reference' => $reference,
            'account_type' => 'sub_agent',
            'account_id' => $SubAgent->id,
            'email' => $SubAgent->email,
            'base_amount' => $amount,
            'processing_fee' => $fee,
            'total_amount' => $total,
            'provider' => $isPaystack ? 'paystack' : 'moolre',
            'status' => 'pending',
        ]);

        $dealer = $SubAgent->agent;

        $result = $gateway->initiate([
            'checkout' => true,
            'email' => $SubAgent->email ?: ($dealer?->email ?? 'customer@example.com'),
            'amount' => $total,
            'reference' => $reference,
            'callback_url' => route('Sub-Agent.topup.callback'),
            'metadata' => [
                'topup_id' => $topup->id,
                'sub_agent_id' => $SubAgent->id,
                'dealer_id' => $SubAgent->agent_id,
                'type' => 'sub_agent_topup',
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
                'authorization_url' => $result['authorization_url'],
            ],
        ]);
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        $cancelled = $request->query('cancelled');

        if ($cancelled) {
            if ($reference) {
                WalletTopup::where('reference', $reference)
                    ->where('status', 'pending')
                    ->update(['status' => 'failed']);
            }
            return redirect()->route('Sub-Agent.topup', ['topup' => 'cancelled']);
        }

        if (!$reference) {
            return redirect()->route('Sub-Agent.topup', ['topup' => 'failed']);
        }

        $topup = WalletTopup::where('reference', $reference)->first();

        if (!$topup || $topup->status === 'completed') {
            return redirect()->route('Sub-Agent.topup', $topup?->status === 'completed' ? ['topup' => 'success'] : ['topup' => 'failed']);
        }

        $SubAgent = SubAgent::find($topup->account_id);

        if (!$SubAgent) {
            $topup->update(['status' => 'failed']);
            return redirect()->route('Sub-Agent.topup', ['topup' => 'failed']);
        }

        $router = app(PaymentRouter::class);
        $gateway = $router->forDealerStore($SubAgent->agent_id);

        $result = $gateway->verify($reference);

        if (!$result['success']) {
            $topup->update(['status' => 'failed']);
            return redirect()->route('Sub-Agent.topup', ['topup' => 'failed']);
        }

        $SubAgent->increment('balance', $topup->base_amount);
        $SubAgent->refresh();

        $topup->update([
            'status' => 'completed',
            'paid_at' => now(),
            'credited_at' => now(),
        ]);

        SubAgentBalanceHistory::create([
            'sub_agent_id' => $SubAgent->id,
            'change_amount' => $topup->base_amount,
            'balance_after' => (float) $SubAgent->balance,
            'reason' => 'topup',
            'ref_id' => $topup->id,
            'description' => 'Wallet top-up via Paystack',
        ]);

        return redirect()->route('Sub-Agent.topup', ['topup' => 'success']);
    }

    public function status(Request $request, $reference)
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $topup = WalletTopup::where('reference', $reference)
            ->where('account_id', $SubAgent->id)
            ->where('account_type', 'sub_agent')
            ->first();

        if (!$topup) {
            return response()->json([
                'success' => false,
                'message' => 'Top-up not found',
            ]);
        }

        if ($topup->status === 'completed') {
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment successful! Wallet credited.',
                'balance' => (float) $SubAgent->balance,
            ]);
        }

        if ($topup->status === 'failed') {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'Payment failed. Please try again.',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'message' => 'Waiting for payment...',
        ]);
    }

    public function verifyPending(Request $request)
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent || !$SubAgent->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or account suspended.',
            ], 403);
        }

        $validated = $request->validate([
            'reference' => 'required|string|max:255',
        ]);

        $reference = $validated['reference'];

        $topup = WalletTopup::where('reference', $reference)
            ->where('account_id', $SubAgent->id)
            ->where('account_type', 'sub_agent')
            ->first();

        if (!$topup) {
            return response()->json([
                'success' => false,
                'message' => 'Top-up record not found.',
            ]);
        }

        if ($topup->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'This top-up was already completed.',
                'balance' => (float) $SubAgent->fresh()->balance,
            ]);
        }

        $router = app(PaymentRouter::class);
        $gateway = $router->forDealerStore($SubAgent->agent_id);

        $result = $gateway->verify($reference);

        if (!$result['success']) {
            $topup->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment verification failed.',
            ]);
        }

        $SubAgent->increment('balance', $topup->base_amount);
        $SubAgent->refresh();

        $topup->update([
            'status' => 'completed',
            'paid_at' => now(),
            'credited_at' => now(),
        ]);

        SubAgentBalanceHistory::create([
            'sub_agent_id' => $SubAgent->id,
            'change_amount' => $topup->base_amount,
            'balance_after' => (float) $SubAgent->balance,
            'reason' => 'topup',
            'ref_id' => $topup->id,
            'description' => 'Manual payment verification via Paystack',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment verified! Wallet credited successfully.',
            'balance' => (float) $SubAgent->balance,
        ]);
    }
}
