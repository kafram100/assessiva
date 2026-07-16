<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubAgent;
use App\Models\WalletTopup;
use App\Models\BalanceHistory;
use App\Models\SubAgentBalanceHistory;
use App\Models\PaymentVerification;
use App\Services\Payment\PaymentRouter;
use Illuminate\Http\Request;

class VerifyPaymentController extends Controller
{
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:255',
            'gateway' => 'nullable|string|in:paystack,moolre',
        ]);

        $reference = $validated['reference'];
        $gatewayName = $validated['gateway'] ?? 'paystack';

        $alreadyVerified = PaymentVerification::where('reference', $reference)->exists();
        if ($alreadyVerified) {
            return response()->json([
                'success' => false,
                'message' => 'This payment reference has already been used.',
            ], 422);
        }

        $alreadyCompleted = WalletTopup::where('reference', $reference)
            ->where('status', 'completed')
            ->exists();
        if ($alreadyCompleted) {
            return response()->json([
                'success' => false,
                'message' => 'This payment reference has already been used.',
            ], 422);
        }

        $gateway = app(PaymentRouter::class)->gateway($gatewayName);
        $result = $gateway->verify($reference);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment verification failed.',
            ]);
        }

        $totalAmount = (float) ($result['amount'] ?? 0);
        if ($totalAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment amount.',
            ]);
        }

        $baseAmount = (float) ($result['metadata']['base_amount'] ?? 0);
        if ($baseAmount <= 0) {
            $existingTopup = WalletTopup::where('reference', $reference)->first();
            if ($existingTopup && $existingTopup->base_amount > 0) {
                $baseAmount = (float) $existingTopup->base_amount;
            } else {
                $baseAmount = round($totalAmount / 1.02, 2);
            }
        }

        $user = $request->user();
        $SubAgentId = session('sub_agent_id');

        if ($SubAgentId) {
            $SubAgent = SubAgent::find($SubAgentId);
            if (!$SubAgent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-Agent account not found.',
                ]);
            }

            $SubAgent->increment('balance', $baseAmount);

            SubAgentBalanceHistory::create([
                'sub_agent_id' => $SubAgent->id,
                'change_amount' => $baseAmount,
                'balance_after' => $SubAgent->fresh()->balance,
                'reason' => 'topup',
                'ref_id' => null,
                'description' => 'Manual verification via ' . ucfirst($gatewayName),
            ]);

            PaymentVerification::create([
                'reference' => $reference,
                'gateway' => $gatewayName,
                'user_type' => 'sub_agent',
                'user_id' => $SubAgent->id,
                'amount' => $baseAmount,
                'verified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified! Balance credited successfully.',
                'balance' => (float) $SubAgent->fresh()->balance,
            ]);
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $user->increment('balance', $baseAmount);
        $newBalance = (float) $user->balance;

        BalanceHistory::create([
            'agent_id' => $user->id,
            'change_amount' => $baseAmount,
            'balance_after' => $newBalance,
            'reason' => 'topup',
            'description' => 'Manual verification via ' . ucfirst($gatewayName),
            'ref_id' => null,
        ]);

        PaymentVerification::create([
            'reference' => $reference,
            'gateway' => $gatewayName,
            'user_type' => $user->role ?? 'agent',
            'user_id' => $user->id,
            'amount' => $baseAmount,
            'verified_at' => now(),
        ]);

        $walletTopup = WalletTopup::where('reference', $reference)->first();
        if ($walletTopup && $walletTopup->status === 'pending') {
            $walletTopup->update(['status' => 'completed', 'paid_at' => now(), 'credited_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment verified! Wallet credited successfully.',
            'balance' => $newBalance,
        ]);
    }
}
