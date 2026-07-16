<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $store = $user->storeSettings()->firstOrCreate(
            ['agent_id' => $user->id],
            [
                'store_name' => $user->full_name ?? $user->username . "'s Store",
                'store_slug' => Str::slug($user->username),
                'is_active' => false,
                'preferred_gateway' => 'paystack',
            ]
        );

        return Inertia::render('Dealer/Store', [
            'store' => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'store_slug' => $store->store_slug,
                'store_description' => $store->store_description,
                'is_active' => $store->is_active,
                'theme_color' => $store->theme_color ?? '#1d4ed8',
                'accent_color' => $store->accent_color ?? '#f59e0b',
                'logo_url' => $store->logo_url,
                'contact_phone' => $store->contact_phone,
                'contact_whatsapp' => $store->contact_whatsapp,
                'hero_headline' => $store->hero_headline,
                'announcement_text' => $store->announcement_text,
                'footer_tagline' => $store->footer_tagline,
                'preferred_gateway' => 'paystack',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'store_name' => 'required|string|max:100',
            'store_slug' => 'required|string|max:100|unique:agent_store_settings,store_slug,' . $user->id . ',agent_id',
            'store_description' => 'nullable|string|max:500',
            'theme_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'contact_phone' => 'nullable|string|max:20',
            'contact_whatsapp' => 'nullable|string|max:20',
            'hero_headline' => 'nullable|string|max:200',
            'announcement_text' => 'nullable|string|max:300',
            'footer_tagline' => 'nullable|string|max:200',
        ]);

        $store = $user->storeSettings()->firstOrCreate(
            ['agent_id' => $user->id],
            ['store_name' => $user->username . "'s Store"]
        );

        $data = $validated;
        $data['store_slug'] = Str::slug($data['store_slug']);
        $data['is_active'] = $request->boolean('is_active', $store->is_active ?? false);
        $data['preferred_gateway'] = 'paystack';

        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = '/storage/' . $path;
        }

        $store->update($data);

        return redirect()->route('dealer.store')
            ->with('success', 'Store settings updated successfully.');
    }
}
