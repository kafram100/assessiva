<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $orders = $user->agentOrders()
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('beneficiary_number', 'like', "%{$search}%")
                      ->orWhere('tracking_key', 'like', "%{$search}%")
                      ->orWhere('external_transaction_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'package_size' => $order->package_size,
                    'amount' => (float) $order->amount,
                    'beneficiary_number' => $order->beneficiary_number,
                    'network_type' => $order->network_type,
                    'status' => $order->status,
                    'tracking_key' => $order->tracking_key,
                    'payment_method' => $order->payment_method,
                    'external_transaction_id' => $order->external_transaction_id,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Agent/Orders', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
        ]);
    }
}
