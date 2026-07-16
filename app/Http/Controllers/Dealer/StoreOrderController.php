<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $storeOrders = StoreOrder::where('agent_id', $user->id)
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->sub_agent_id, function ($q, $id) {
                $q->where('sub_agent_id', $id);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('customer_phone', 'like', "%{$search}%")
                      ->orWhere('order_ref', 'like', "%{$search}%")
                      ->orWhere('tracking_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'order_ref' => $order->order_ref,
                    'tracking_id' => $order->tracking_id,
                    'sub_agent' => $order->SubAgent?->username ?? '—',
                    'customer_phone' => $order->customer_phone,
                    'customer_name' => $order->customer_name,
                    'network_type' => $order->network_type,
                    'package_size_gb' => (float) $order->package_size_gb,
                    'base_price' => (float) $order->base_price,
                    'sold_price' => (float) $order->sold_price,
                    'agent_profit' => (float) $order->agent_profit,
                    'your_profit' => (float) $order->your_profit,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                ];
            });

        $SubAgents = $user->subAgents()
            ->where('is_active', true)
            ->get(['id', 'username']);

        return Inertia::render('Dealer/StoreOrders', [
            'storeOrders' => $storeOrders,
            'SubAgents' => $SubAgents,
            'filters' => $request->only(['status', 'sub_agent_id', 'search']),
        ]);
    }
}
