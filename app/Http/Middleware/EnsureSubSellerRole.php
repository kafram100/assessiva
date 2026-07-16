<?php

namespace App\Http\Middleware;

use App\Models\SubSeller;
use Closure;
use Illuminate\Http\Request;

class EnsureSubSellerRole
{
    public function handle(Request $request, Closure $next)
    {
        $subSellerId = (int) $request->session()->get('sub_seller_id');

        if (!$subSellerId) {
            return redirect()->route('sub-seller.login');
        }

        $subSeller = SubSeller::find($subSellerId);

        if (!$subSeller || !$subSeller->is_active) {
            $request->session()->forget('sub_seller_id');
            $request->session()->regenerate();
            $request->session()->regenerateToken();
            return redirect()->route('sub-seller.login')->withErrors([
                'username' => 'Your account has been suspended.',
            ]);
        }

        $request->merge(['sub_seller' => $subSeller]);
        $request->setUserResolver(function () use ($subSeller) {
            return $subSeller;
        });

        return $next($request);
    }
}
