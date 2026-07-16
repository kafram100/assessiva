<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\AgentPackage;
use App\Models\AgentPackagePricing;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricelistController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $packages = AgentPackage::where('is_active', true)
            ->where(function ($q) {
                $q->where('role', 'dealer')->orWhere('role', 'all');
            })
            ->where('network_type', '!=', 'ALL')
            ->where('manual_fulfillment', false)
            ->orderByRaw("CASE WHEN network_type = 'MTN' THEN 0 WHEN network_type = 'VODAFONE' THEN 1 WHEN network_type = 'AIRTELTIGO' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn ($pkg) => $pkg->network_type . '_' . $pkg->package_size_gb)
            ->map(function ($group) {
                return $group->firstWhere('role', 'dealer') ?? $group->first();
            })
            ->values()
            ->sortBy('sort_order')
            ->map(function ($pkg) use ($user) {
                $pricing = AgentPackagePricing::where('agent_id', $user->id)
                    ->where('package_id', $pkg->id)
                    ->first();

                return [
                    'id' => $pkg->id,
                    'network_type' => $pkg->network_type,
                    'package_name' => $pkg->package_name,
                    'package_size' => $pkg->package_size_gb
                        ? $pkg->package_size_gb . ' GB'
                        : ($pkg->package_size_mb . ' MB'),
                    'base_price' => (float) $pkg->price,
                    'customer_price' => $pricing ? (float) $pricing->customer_price : null,
                    'sub_agent_price' => $pricing ? (float) $pricing->sub_agent_price : null,
                ];
            })
            ->groupBy('network_type');

        return Inertia::render('Dealer/Pricelist', [
            'packagesData' => $packages,
            'networks' => $packages->keys(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'prices' => 'required|array',
            'prices.*.package_id' => 'required|exists:agent_packages,id',
            'prices.*.customer_price' => 'required|numeric|min:0',
            'prices.*.sub_agent_price' => 'required|numeric|min:0',
        ]);

        foreach ($validated['prices'] as $item) {
            AgentPackagePricing::updateOrCreate(
                ['agent_id' => $user->id, 'package_id' => $item['package_id']],
                [
                    'customer_price' => $item['customer_price'],
                    'sub_agent_price' => $item['sub_agent_price'],
                ]
            );
        }

        return back()->with('success', 'Pricelist updated successfully.');
    }


}
