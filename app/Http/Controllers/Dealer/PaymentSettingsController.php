<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentSettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $store = $user->storeSettings()->firstOrCreate(
            ['agent_id' => $user->id],
            [
                'store_name' => $user->full_name ?? $user->username . "'s Store",
                'store_slug' => \Illuminate\Support\Str::slug($user->username),
                'is_active' => false,
                'preferred_gateway' => 'paystack',
            ]
        );

        return Inertia::render('Dealer/PaymentSettings', [
            'paystack_public_key' => $store->paystack_public_key,
            'paystack_secret_key' => $store->paystack_secret_key ? '••••••••' . substr($store->paystack_secret_key, -4) : null,
            'has_secret_key' => !is_null($store->paystack_secret_key),
            'platform_public_key' => config('services.paystack.public_key'),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'paystack_public_key' => 'nullable|string|max:255',
            'paystack_secret_key' => 'nullable|string|max:255',
        ]);

        $store = $user->storeSettings()->firstOrCreate(
            ['agent_id' => $user->id],
            ['store_name' => $user->username . "'s Store"]
        );

        $data = [];

        if (!empty($validated['paystack_public_key'])) {
            $data['paystack_public_key'] = $validated['paystack_public_key'];
        }

        if (!empty($validated['paystack_secret_key'])) {
            $data['paystack_secret_key'] = $validated['paystack_secret_key'];
        }

        $store->update($data);

        return redirect()->route('dealer.payment-settings')
            ->with('success', 'Payment settings updated successfully.');
    }
}
