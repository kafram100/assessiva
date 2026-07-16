<?php

namespace App\Http\Controllers\SubAgent;

use App\Http\Controllers\Controller;
use App\Models\AgentPackage;
use App\Models\AgentPackagePricing;
use App\Models\SubAgent;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $SubAgent = SubAgent::find(session('sub_agent_id'));

        if (!$SubAgent) {
            return redirect()->route('Sub-Agent.login');
        }

        $dealerId = $SubAgent->agent_id;

        $packages = AgentPackage::with(['agentPackagePricing' => fn ($q) => $q
                ->where('agent_id', $dealerId)
                ->where('is_active', true)])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('role', 'dealer')->orWhere('role', 'all');
            })
            ->where('network_type', '!=', 'ALL')
            ->orderByRaw("CASE WHEN network_type = 'MTN' THEN 0 WHEN network_type = 'VODAFONE' THEN 1 WHEN network_type = 'AIRTELTIGO' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn ($pkg) => $pkg->network_type . '_' . $pkg->package_size_gb)
            ->map(function ($group) {
                return $group->firstWhere('role', 'dealer') ?? $group->first();
            })
            ->values()
            ->sortBy('sort_order')
            ->filter(function ($pkg) {
                if ($pkg->manual_fulfillment) return false;
                $pricing = $pkg->agentPackagePricing->first();
                if (!$pricing) return false;
                if ((float) $pricing->sub_agent_price === (float) $pkg->price) return false;
                return true;
            })
            ->map(function ($pkg) {
                $customPricing = $pkg->agentPackagePricing->first();
                return [
                    'id' => $pkg->id,
                    'network_type' => $pkg->network_type,
                    'package_name' => $pkg->package_name,
                    'package_size' => $pkg->package_size_gb
                        ? ($pkg->package_size_gb . ' GB')
                        : ($pkg->package_size_mb . ' MB'),
                    'price' => (float) $customPricing->sub_agent_price,
                    'sort_order' => $pkg->sort_order,
                ];
            })
            ->groupBy('network_type');

        return Inertia::render('SubAgent/Dashboard', [
            'packages' => $packages,
            'networks' => $packages->keys(),
            'balance' => (float) $SubAgent->balance,
            'subAgent' => [
                'id' => $SubAgent->id,
                'username' => $SubAgent->username,
            ],
        ]);
    }
}
