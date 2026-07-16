<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\AgentPackage;
use Inertia\Inertia;

class BuyDataController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $packages = AgentPackage::where('is_active', true)
            ->whereIn('role', ['all', 'dealer'])
            ->where('network_type', '!=', 'ALL')
            ->where('manual_fulfillment', false)
            ->orderByRaw("CASE WHEN network_type = 'MTN' THEN 0 WHEN network_type = 'VODAFONE' THEN 1 WHEN network_type = 'AIRTELTIGO' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')
            ->get()
            ->groupBy(function ($pkg) {
                $size = $pkg->package_size_gb ?: round($pkg->package_size_mb / 1024, 1);
                return $pkg->network_type . '_' . $size;
            })
            ->map(function ($group) {
                return $group->firstWhere('role', 'dealer') ?: $group->first();
            })
            ->values()
            ->sortBy('sort_order')
            ->map(function ($pkg) {
                return [
                    'id' => $pkg->id,
                    'network_type' => $pkg->network_type,
                    'package_name' => $pkg->package_name,
                    'package_size' => $pkg->package_size_gb
                        ? ($pkg->package_size_gb . ' GB')
                        : ($pkg->package_size_mb . ' MB'),
                    'price' => (float) $pkg->price,
                    'sort_order' => $pkg->sort_order,
                ];
            })
            ->groupBy('network_type');

        return Inertia::render('Dealer/BuyData', [
            'packagesData' => $packages,
            'networks' => $packages->keys(),
            'balance' => (float) $user->balance,
        ]);
    }
}
