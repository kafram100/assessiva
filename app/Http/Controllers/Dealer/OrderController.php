<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $agentOrders = $user->agentOrders()
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
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'agent_' . $order->id,
                    'package_size' => $order->package_size,
                    'amount' => (float) $order->amount,
                    'beneficiary_number' => $order->beneficiary_number,
                    'network_type' => $order->network_type,
                    'status' => $order->status,
                    'tracking_key' => $order->tracking_key,
                    'payment_method' => $order->payment_method,
                    'external_transaction_id' => $order->external_transaction_id,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                    'type' => 'Agent',
                ];
            });

        $storeOrders = $user->storeOrders()
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('customer_phone', 'like', "%{$search}%")
                      ->orWhere('order_ref', 'like', "%{$search}%")
                      ->orWhere('tracking_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'store_' . $order->id,
                    'package_size' => ((float) $order->package_size_gb) . ' GB',
                    'amount' => (float) $order->sold_price,
                    'beneficiary_number' => $order->customer_phone,
                    'network_type' => $order->network_type,
                    'status' => $order->status,
                    'tracking_key' => $order->tracking_id ?? '-',
                    'payment_method' => $order->payment_method ?? '-',
                    'external_transaction_id' => $order->external_transaction_id ?? '-',
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                    'type' => $order->sub_agent_id ? 'Sub-Agent' : 'Storefront',
                ];
            });

        $merged = collect(array_merge($agentOrders->toArray(), $storeOrders->toArray()))
            ->sortByDesc('created_at')
            ->values();

        $perPage = 15;
        $page = $request->input('page', 1);
        $total = $merged->count();
        $items = $merged->forPage($page, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $paginator->getCollection()->transform(function ($item) {
            return (object) $item;
        });

        return Inertia::render('Dealer/Orders', [
            'orders' => $paginator,
            'filters' => $request->only(['status', 'search']),
        ]);
    }
}
