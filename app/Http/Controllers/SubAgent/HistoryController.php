<?php

namespace App\Http\Controllers\SubAgent;

use App\Http\Controllers\Controller;
use App\Models\SubAgent;
use App\Models\SubAgentBalanceHistory;
use Inertia\Inertia;

class HistoryController extends Controller
{
    public function index()
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent) {
            return redirect()->route('Sub-Agent.login');
        }

        $history = SubAgentBalanceHistory::where('sub_agent_id', $SubAgent->id)
            ->latest()
            ->paginate(20)
            ->through(function ($entry) {
                return [
                    'id' => $entry->id,
                    'change_amount' => (float) $entry->change_amount,
                    'balance_after' => (float) $entry->balance_after,
                    'reason' => $entry->reason,
                    'description' => $entry->description,
                    'created_at' => $entry->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('SubAgent/History', [
            'history' => $history,
        ]);
    }
}
