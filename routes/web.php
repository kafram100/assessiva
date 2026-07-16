<?php

use App\Http\Controllers\Api\AgentOrderController;
use App\Http\Controllers\Api\WalletTopupController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('BuyData');
})->name('home');

Route::get('/track/{reference?}', function ($reference = null) {
    return Inertia::render('TrackOrder', ['reference' => $reference]);
})->name('track');

Route::get('/order/{id}/success', function ($id) {
    return Inertia::render('OrderSuccess', ['orderId' => (int) $id]);
})->name('order.success');

Route::post('/wallet/topup/moolre/callback', [App\Http\Controllers\Api\WalletTopupController::class, 'moolreCallback'])
    ->name('wallet.topup.moolre.callback');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/wallet/topup/moolre/redirect', [App\Http\Controllers\Api\WalletTopupController::class, 'moolreRedirect'])
        ->name('wallet.topup.moolre.redirect');

    Route::get('/wallet/topup/paystack/callback', [App\Http\Controllers\Api\WalletTopupController::class, 'paystackCallback'])
        ->name('wallet.topup.paystack.callback');

    Route::post('/wallet/topup', [App\Http\Controllers\Api\WalletTopupController::class, 'initiate'])->name('wallet.topup.initiate');
    Route::get('/wallet/topup/{reference}/status', [App\Http\Controllers\Api\WalletTopupController::class, 'status'])->name('wallet.topup.status');
    Route::post('/wallet/topup/verify-pending', [App\Http\Controllers\Api\WalletTopupController::class, 'verifyPending'])->name('wallet.topup.verify-pending');
    Route::post('/wallet/topup/{reference}/verify', [App\Http\Controllers\Api\WalletTopupController::class, 'verifyTopup'])->name('wallet.topup.verify-single');

    Route::get('/dashboard', function () {
        $user = request()->user();
        if ($user->role === 'dealer') {
            return app(\App\Http\Controllers\Dealer\DashboardController::class)->index();
        }
        return app(\App\Http\Controllers\Agent\DashboardController::class)->index();
    })->name('dashboard');

    Route::middleware('agent')->group(function () {
        Route::get('/agent/orders', [App\Http\Controllers\Agent\OrderController::class, 'index'])
            ->name('agent.orders');

        Route::get('/agent/wallet', [App\Http\Controllers\Agent\WalletController::class, 'index'])
            ->name('agent.wallet');

        Route::get('/agent/buy-data', function () {
        $user = request()->user();

        $packages = \App\Models\AgentPackage::where('is_active', true)
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

        return Inertia::render('Agent/BuyData', [
            'packagesData' => $packages,
            'networks' => $packages->keys(),
            'balance' => (float) $user->balance,
        ]);
    })->name('agent.buy-data');

    Route::get('/agent/mashup-data', function () {
        $user = request()->user();
        $packages = \App\Models\AgentPackage::where('manual_fulfillment', true)
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('role', $user->role)->orWhere('role', 'all');
            })
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($pkg) => [
                'id' => $pkg->id,
                'package_name' => $pkg->package_name,
                'package_size_gb' => (float) $pkg->package_size_gb,
                'voice_minutes' => (int) $pkg->voice_minutes,
                'price' => (float) $pkg->price,
                'non_expiring' => (bool) $pkg->non_expiring,
            ]);

        return Inertia::render('MashupData', [
            'packages' => $packages,
            'balance' => (float) $user->balance,
        ]);
    })->name('agent.mashup-data');

    Route::post('/agent/order', [AgentOrderController::class, 'placeOrder'])->name('agent.order.place')->middleware('api.active');
    }); // end agent middleware

    Route::middleware('dealer')->group(function () {
        Route::get('/dealer/buy-data', [App\Http\Controllers\Dealer\BuyDataController::class, 'index'])
            ->name('dealer.buy-data');

        Route::get('/dealer/mashup-data', function () {
            $user = request()->user();
            $packages = \App\Models\AgentPackage::where('manual_fulfillment', true)
                ->where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('role', $user->role)->orWhere('role', 'all');
                })
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'package_name' => $pkg->package_name,
                    'package_size_gb' => (float) $pkg->package_size_gb,
                    'voice_minutes' => (int) $pkg->voice_minutes,
                    'price' => (float) $pkg->price,
                    'non_expiring' => (bool) $pkg->non_expiring,
                ]);

            return Inertia::render('MashupData', [
                'packages' => $packages,
                'balance' => (float) $user->balance,
            ]);
        })->name('dealer.mashup-data');

        Route::get('/dealer/orders', [App\Http\Controllers\Dealer\OrderController::class, 'index'])
            ->name('dealer.orders');

        Route::get('/dealer/wallet', [App\Http\Controllers\Dealer\WalletController::class, 'index'])
            ->name('dealer.wallet');

        Route::get('/dealer/Sub-Agents', [App\Http\Controllers\Dealer\SubAgentController::class, 'index'])
            ->name('dealer.Sub-Agents');

        Route::post('/dealer/Sub-Agents', [App\Http\Controllers\Dealer\SubAgentController::class, 'store'])
            ->name('dealer.Sub-Agents.store');

        Route::put('/dealer/Sub-Agents/{SubAgent}', [App\Http\Controllers\Dealer\SubAgentController::class, 'update'])
            ->name('dealer.Sub-Agents.update');

        Route::post('/dealer/Sub-Agents/{SubAgent}/credit', [App\Http\Controllers\Dealer\SubAgentController::class, 'credit'])
            ->name('dealer.Sub-Agents.credit');

        Route::post('/dealer/Sub-Agents/{SubAgent}/reset-password', [App\Http\Controllers\Dealer\SubAgentController::class, 'resetPassword'])
            ->name('dealer.Sub-Agents.reset-password');

        Route::get('/dealer/store', [App\Http\Controllers\Dealer\StoreController::class, 'index'])
            ->name('dealer.store');

        Route::post('/dealer/store', [App\Http\Controllers\Dealer\StoreController::class, 'update'])
            ->name('dealer.store.update');

        Route::get('/dealer/store-orders', [App\Http\Controllers\Dealer\StoreOrderController::class, 'index'])
            ->name('dealer.store-orders');

        Route::get('/dealer/payment-settings', [App\Http\Controllers\Dealer\PaymentSettingsController::class, 'index'])
            ->name('dealer.payment-settings');

        Route::post('/dealer/payment-settings', [App\Http\Controllers\Dealer\PaymentSettingsController::class, 'update'])
            ->name('dealer.payment-settings.update');

        Route::get('/dealer/pricelist', [App\Http\Controllers\Dealer\PricelistController::class, 'index'])
            ->name('dealer.pricelist');

        Route::post('/dealer/pricelist', [App\Http\Controllers\Dealer\PricelistController::class, 'update'])
            ->name('dealer.pricelist.update');
    }); // end dealer middleware

    Route::post('/verify-payment', [App\Http\Controllers\Api\VerifyPaymentController::class, 'verify'])->name('verify-payment');
}); // end auth middleware

// Fallback for Filament admin login if submitted natively
Route::post('/admin/login', function () {
    return redirect()->route('filament.admin.auth.login');
});

// Admin dashboard quick actions
Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    Route::post('/toggle-role/{user}', function (\App\Models\User $user) {
        $user->update(['role' => $user->role === 'agent' ? 'dealer' : 'agent']);
        return back();
    })->name('admin.toggle-role');

    Route::post('/toggle-status/{user}', function (\App\Models\User $user) {
        $user->update(['status' => $user->status === 'active' ? 'suspended' : 'active']);
        return back();
    })->name('admin.toggle-status');

    Route::post('/credit-debit/{user}', function (\App\Models\User $user) {
        $amount = (float) request('amount', 0);
        if ($amount == 0) {
            return back()->with('error', 'Amount must not be zero.');
        }
        $oldBalance = $user->balance;
        $user->increment('balance', $amount);
        $newBalance = $oldBalance + $amount;
        if ($amount > 0) {
            $topup = \App\Models\WalletTopup::create([
                'reference' => 'ADM-' . $user->id . '-' . now()->timestamp,
                'account_type' => $user->role ?? 'agent',
                'account_id' => $user->id,
                'email' => $user->email,
                'base_amount' => $amount,
                'processing_fee' => 0,
                'total_amount' => $amount,
                'provider' => 'admin',
                'status' => 'completed',
                'credited_at' => now(),
            ]);
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WalletTopupMail($topup, $user, (float) $newBalance));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        \App\Models\BalanceHistory::create([
            'agent_id' => $user->id,
            'change_amount' => $amount,
            'balance_after' => $newBalance,
            'reason' => $amount > 0 ? 'admin_credit' : 'admin_debit',
            'description' => $amount > 0 ? 'Admin credited account' : 'Admin debited account',
        ]);
        $action = $amount > 0 ? 'credited' : 'debited';
        return back()->with('success', "Account {$action} by GHS " . number_format(abs($amount), 2) . '.');
    })->name('admin.credit-debit');

    Route::post('/user-update/{user}', function (\App\Models\User $user) {
        $data = request()->validate([
            'password' => 'nullable|string|min:6|max:12|confirmed',
        ]);
        if (!empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }
        return back()->with('success', 'Password updated successfully.');
    })->name('admin.user-update');

    Route::post('/user-delete/{user}', function (\App\Models\User $user) {
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return back()->with('error', 'Cannot delete admin accounts.');
        }
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
                // Delete sub-agents and their associated store orders
                $subAgents = \App\Models\SubAgent::where('agent_id', $user->id)->get();
                foreach ($subAgents as $subAgent) {
                    \App\Models\StoreOrder::where('sub_agent_id', $subAgent->id)->delete();
                    \App\Models\SubAgentBalanceHistory::where('sub_agent_id', $subAgent->id)->delete();
                    $subAgent->delete();
                }

                // Delete agent's own records
                \App\Models\AgentOrder::where('agent_id', $user->id)->delete();
                \App\Models\StoreOrder::where('agent_id', $user->id)->delete();
                \App\Models\Order::where('user_id', $user->id)->delete();
                \App\Models\BalanceHistory::where('agent_id', $user->id)->delete();
                \App\Models\AgentStoreSetting::where('agent_id', $user->id)->delete();
                \App\Models\AgentPackagePricing::where('agent_id', $user->id)->delete();
                \App\Models\WalletTopup::where('account_id', $user->id)->delete();
                \App\Models\AgentPricingRule::where('agent_id', $user->id)->delete();
                \App\Models\Notification::where('recipient_id', $user->id)->delete();
                
                $user->delete();
            });
            return back()->with('success', 'Account and all associated records deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delete user failed: ' . $e->getMessage());
            return back()->with('error', 'Error deleting account: ' . $e->getMessage());
        }
    })->name('admin.user-delete');

    Route::get('/profit-month', function (\Illuminate\Http\Request $request) {
        $month = $request->input('month', now()->format('Y-m'));
        $start = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $end = \Carbon\Carbon::parse($month . '-01')->endOfMonth();
        return response()->json(\App\Filament\Pages\Dashboard::calcProfit($start, $end));
    })->name('admin.profit-month');

    Route::post('/user-create', function () {
            $data = request()->validate([
                'username' => 'required|string|max:50|unique:users,username',
                'email' => 'required|email|max:100|unique:users,email',
                'password' => 'required|string|min:6|max:12|confirmed',
                'role' => 'required|in:agent,dealer',
            ]);
            \App\Models\User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'balance' => 0.00,
                'status' => 'active',
            ]);
            return response()->json(['success' => true]);
        })->name('admin.user-create');

    Route::post('/customer-packages', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'network_type' => 'required|string|in:MTN,VODAFONE,AIRTELTIGO,ALL',
            'package_name' => 'required|string|max:100',
            'package_size' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $isActive = $data['is_active'] ?? true;
        if ($data['network_type'] === 'ALL') {
            foreach (['MTN', 'VODAFONE', 'AIRTELTIGO'] as $network) {
                \App\Models\CustomerPackage::create([
                    'network_type' => $network,
                    'package_name' => $data['package_name'],
                    'package_size' => $data['package_size'],
                    'price' => $data['price'],
                    'is_active' => $isActive,
                ]);
            }
        } else {
            \App\Models\CustomerPackage::create([
                'network_type' => $data['network_type'],
                'package_name' => $data['package_name'],
                'package_size' => $data['package_size'],
                'price' => $data['price'],
                'is_active' => $isActive,
            ]);
        }
        return response()->json(['success' => true]);
    })->name('admin.customer-packages.store');

    // Admin chat management
    Route::get('/chat/tickets', [App\Http\Controllers\Admin\ChatController::class, 'tickets']);
    Route::get('/chat/tickets/{id}', [App\Http\Controllers\Admin\ChatController::class, 'messages']);
    Route::post('/chat/tickets/{id}/reply', [App\Http\Controllers\Admin\ChatController::class, 'reply']);

    // Bulk status update for MTN Mashup orders
    Route::post('/mashup/bulk-status', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'selected' => 'required|array',
            'selected.*.id' => 'required|integer',
            'selected.*.source' => 'required|string|in:Agent Order,Dealer Order,Store Order,Public Customer',
            'status' => 'required|string|in:pending,processing,delivered,failed,cancelled',
        ]);

        $updated = 0;
        foreach ($data['selected'] as $item) {
            try {
                match ($item['source']) {
                    'Agent Order', 'Dealer Order' => \App\Models\AgentOrder::where('id', $item['id'])->update(['status' => $data['status']]),
                    'Store Order' => \App\Models\StoreOrder::where('id', $item['id'])->update(['status' => $data['status']]),
                    'Public Customer' => \App\Models\Order::where('id', $item['id'])->update(['status' => $data['status']]),
                };
                $updated++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    })->name('admin.mashup.bulk-status');
});

require __DIR__.'/auth.php';

// Support chat - public, no auth
Route::post('/support/start', [App\Http\Controllers\SupportController::class, 'start']);
Route::post('/support/message', [App\Http\Controllers\SupportController::class, 'sendMessage']);
Route::get('/support/messages', [App\Http\Controllers\SupportController::class, 'getMessages']);

// Storefront - public, no auth
Route::get('/store/payment/callback', [App\Http\Controllers\StorefrontController::class, 'paymentCallback'])
    ->name('store.payment.callback');

Route::get('/store/{slug}/order/{orderId}/success', [App\Http\Controllers\StorefrontController::class, 'orderSuccess'])
    ->where(['slug' => '[a-z0-9-]+', 'orderId' => '[0-9]+'])
    ->name('store.order.success');

Route::get('/store/{slug}', [App\Http\Controllers\StorefrontController::class, 'index'])
    ->where(['slug' => '[a-z0-9-]+'])
    ->name('store.storefront');

Route::post('/store/{slug}/checkout', [App\Http\Controllers\StorefrontController::class, 'checkout'])
    ->where(['slug' => '[a-z0-9-]+'])
    ->name('store.checkout')
    ->middleware('api.active');

// Sub-Agent auth
Route::prefix('Sub-Agent')->name('Sub-Agent.')->group(function () {
    Route::get('/login', [App\Http\Controllers\SubAgent\Auth\LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [App\Http\Controllers\SubAgent\Auth\LoginController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('/logout', [App\Http\Controllers\SubAgent\Auth\LoginController::class, 'logout'])
        ->name('logout');
});

// Sub-Agent topup callback (no auth — Paystack redirect must work regardless of session)
Route::prefix('Sub-Agent')->name('Sub-Agent.')->group(function () {
    Route::get('/topup/callback', [App\Http\Controllers\SubAgent\TopupController::class, 'callback'])
        ->name('topup.callback');
});

// Sub-Agent protected routes
Route::middleware('sub_agent')->prefix('Sub-Agent')->name('Sub-Agent.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\SubAgent\DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/orders', [App\Http\Controllers\SubAgent\OrderController::class, 'index'])
        ->name('orders');

    Route::post('/order', [App\Http\Controllers\SubAgent\OrderController::class, 'placeOrder'])
        ->name('order.place')
        ->middleware('api.active');

    Route::get('/topup', [App\Http\Controllers\SubAgent\TopupController::class, 'showForm'])
        ->name('topup');

    Route::post('/topup', [App\Http\Controllers\SubAgent\TopupController::class, 'initiate'])
        ->name('topup.initiate');

    Route::get('/topup/{reference}/status', [App\Http\Controllers\SubAgent\TopupController::class, 'status'])
        ->name('topup.status');

    Route::post('/topup/verify', [App\Http\Controllers\SubAgent\TopupController::class, 'verifyPending'])
        ->name('topup.verify');

    Route::get('/history', [App\Http\Controllers\SubAgent\HistoryController::class, 'index'])
        ->name('history');

    Route::post('/verify-payment', [App\Http\Controllers\Api\VerifyPaymentController::class, 'verify'])
        ->name('topup.verify-payment');
});

// Removed fallback route
