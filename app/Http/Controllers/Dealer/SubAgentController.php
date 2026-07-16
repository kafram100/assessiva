<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\SubAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\SubAgentBalanceHistory;
use Inertia\Inertia;

class SubAgentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $SubAgents = $user->subAgents()
            ->latest()
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'username' => $sub->username,
                    'email' => $sub->email,
                    'balance' => (float) $sub->balance,
                    'is_active' => $sub->is_active,
                    'ref_code' => $sub->ref_code,
                    'order_count' => $sub->storeOrders()->count(),
                    'created_at' => $sub->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Dealer/SubAgents', [
            'SubAgents' => $SubAgents,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:sub_agents,username',
            'email' => 'required|email|max:100|unique:sub_agents,email',
            'password' => 'required|string|min:6|max:12',
        ]);

        $SubAgent = $user->subAgents()->create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'balance' => 0,
            'ref_code' => strtoupper(Str::random(6)),
            'is_active' => true,
        ]);
        $SubAgent->password_hash = bcrypt($validated['password']);
        $SubAgent->save();

        return redirect()->route('dealer.Sub-Agents')
            ->with('success', "Sub-Agent '{$SubAgent->username}' created successfully.");
    }

    public function update(Request $request, SubAgent $SubAgent)
    {
        $user = auth()->user();

        if ($SubAgent->agent_id != $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:sub_agents,username,' . $SubAgent->id,
            'email' => 'required|email|max:100|unique:sub_agents,email,' . $SubAgent->id,
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6|max:12',
        ]);

        $data = [
            'username' => $validated['username'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active', $SubAgent->is_active),
        ];

        if (!empty($validated['password'])) {
            $SubAgent->password_hash = bcrypt($validated['password']);
        }

        $SubAgent->update($data);

        return redirect()->route('dealer.Sub-Agents')
            ->with('success', "Sub-Agent '{$SubAgent->username}' updated successfully.");
    }

    public function credit(Request $request, SubAgent $SubAgent)
    {
        $user = auth()->user();

        if ($SubAgent->agent_id != $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric',
        ]);

        $amount = (float) $validated['amount'];

        if ($amount == 0) {
            return back()->with('error', 'Amount must not be zero.');
        }

        if ($amount < 0) {
            $debitAmount = abs($amount);
            $affected = SubAgent::where('id', $SubAgent->id)
                ->where('balance', '>=', $debitAmount)
                ->decrement('balance', $debitAmount);
            if (!$affected) {
                return back()->with('error', "Insufficient balance. '{$SubAgent->username}' has GH₵ {$SubAgent->balance}.");
            }
            $action = 'Debited';
        } else {
            $SubAgent->increment('balance', $amount);
            $action = 'Credited';
        }

        $SubAgent->refresh();

        SubAgentBalanceHistory::create([
            'sub_agent_id' => $SubAgent->id,
            'change_amount' => $amount,
            'balance_after' => (float) $SubAgent->balance,
            'reason' => 'manual_adjustment',
            'description' => "Manual {$action} by dealer",
        ]);

        return redirect()->route('dealer.Sub-Agents')
            ->with('success', "{$action} GH₵ " . number_format(abs($amount), 2) . " to/from '{$SubAgent->username}'.");
    }

    public function resetPassword(Request $request, SubAgent $SubAgent)
    {
        $user = auth()->user();

        if ($SubAgent->agent_id != $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6|max:12',
        ]);

        $SubAgent->password_hash = bcrypt($validated['password']);
        $SubAgent->save();

        return redirect()->route('dealer.Sub-Agents')
            ->with('success', "Password reset for '{$SubAgent->username}'.");
    }
}
