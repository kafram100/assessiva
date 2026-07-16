<?php

use App\Http\Controllers\Api\DataBundleController;
use App\Http\Controllers\Api\PublicOrderController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok', 'app' => 'AirLinks', 'version' => '2.0']);

Route::get('/packages', [PublicOrderController::class, 'packages'])->name('api.packages');
Route::post('/checkout', [PublicOrderController::class, 'checkout'])->name('api.checkout')->middleware('api.active');
Route::get('/orders/{id}', [PublicOrderController::class, 'show'])->name('api.orders.show');
Route::get('/orders/track/{reference}', [PublicOrderController::class, 'track'])->name('api.orders.track');
Route::any('/payment/callback', [PublicOrderController::class, 'paymentCallback'])->name('api.payment.callback');

// Data bundle provider API (mirrors GHDataConnect spec, routes through active provider)
Route::prefix('v1')->group(function () {
    Route::get('/getAllNetworks', [DataBundleController::class, 'getAllNetworks']);
    Route::get('/getWalletBalance', [DataBundleController::class, 'getWalletBalance']);
    Route::post('/purchaseBundle', [DataBundleController::class, 'purchaseBundle']);
    Route::post('/createIshareBundleOrder', [DataBundleController::class, 'createIshareBundleOrder']);
    Route::get('/checkOrderStatus/{reference}', [DataBundleController::class, 'checkOrderStatus']);
    Route::get('/active-provider', [DataBundleController::class, 'getActiveProvider']);
});

// DataFlow Ghana API routes
Route::prefix('dataflow')->group(function () {
    Route::post('/normal-orders', [DataBundleController::class, 'dataflowCreateOrder']);
    Route::get('/normal-orders', [DataBundleController::class, 'dataflowOrders']);
    Route::get('/transactions', [DataBundleController::class, 'dataflowTransactions']);
    Route::get('/transactions/{id}', [DataBundleController::class, 'dataflowTransaction']);
    Route::get('/transaction-status', [DataBundleController::class, 'dataflowTransactionStatus']);
    Route::get('/afa/products', [DataBundleController::class, 'dataflowAfaProducts']);
    Route::post('/afa', [DataBundleController::class, 'dataflowCreateAfa']);
});

// Universal Webhook for Data Providers (PortalGH, DataFlow, GHDataConnect)
Route::post('/webhook/data-provider', [WebhookController::class, 'handlePortalGhWebhook'])->name('api.webhook.data-provider');
