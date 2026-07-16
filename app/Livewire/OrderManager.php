<?php

namespace App\Livewire;

use App\Models\AgentOrder;
use App\Models\Order;
use App\Models\StoreOrder;
use Livewire\Component;

class OrderManager extends Component
{
    public $customerOrders = [];
    public $agentDealerOrders = [];

    public $selectedCustomer = [];
    public $selectedAgentDealer = [];

    public $showModal = false;
    public $selectedOrder = [];

    public $customerStatusFilter = '';
    public $customerNetworkFilter = '';
    public $agentStatusFilter = '';
    public $agentNetworkFilter = '';
    public $customerSearch = '';
    public $agentDealerSearch = '';
    
    public $dateFrom = '';
    public $dateTo = '';

    public $pendingCount = 0;
    public $processingCount = 0;
    public $completedCount = 0;
    public $failedCount = 0;

    public function exportCustomerOrders()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=customer_orders.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = Order::query();

        if ($this->customerStatusFilter) {
            $query->where('status', $this->customerStatusFilter);
        }
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->customerSearch) {
            $s = $this->customerSearch;
            $query->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('momo_number', 'like', "%{$s}%")
                  ->orWhere('beneficiary_number', 'like', "%{$s}%");
            });
        }
        if (!empty($this->customerNetworkFilter)) {
            $query->where('network_type', $this->customerNetworkFilter);
        }

        $callback = function() use($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Phone', 'Network', 'Package', 'Amount', 'Status', 'Date']);

            $query->orderBy('created_at', 'desc')->chunk(500, function($orders) use($file) {
                foreach ($orders as $o) {
                    fputcsv($file, [
                        $o->id,
                        $o->momo_number,
                        $o->network_type,
                        $o->package_name ?: $o->package_size,
                        $o->amount,
                        $o->status,
                        $o->created_at?->toDateTimeString()
                    ]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAgentDealerOrders()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=agent_dealer_orders.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $agentQuery = AgentOrder::with('agent')->whereDoesntHave('package', fn($q) => $q->where('manual_fulfillment', true));
        $storeQuery = StoreOrder::with('agent');

        if ($this->dateFrom) {
            $agentQuery->whereDate('created_at', '>=', $this->dateFrom);
            $storeQuery->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $agentQuery->whereDate('created_at', '<=', $this->dateTo);
            $storeQuery->whereDate('created_at', '<=', $this->dateTo);
        }
        if (!empty($this->agentNetworkFilter)) {
            $agentQuery->where('network_type', $this->agentNetworkFilter);
            $storeQuery->where('network_type', $this->agentNetworkFilter);
        }
        if ($this->agentDealerSearch) {
            $s = $this->agentDealerSearch;
            $agentQuery->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('beneficiary_number', 'like', "%{$s}%")
                  ->orWhereHas('agent', fn($q) => $q->where('name', 'like', "%{$s}%"));
            });
            $storeQuery->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('beneficiary_number', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%")
                  ->orWhereHas('agent', fn($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($this->agentStatusFilter) {
            $agentQuery->where('status', $this->agentStatusFilter);
            $storeQuery->where('status', $this->agentStatusFilter);
        }

        $callback = function() use($agentQuery, $storeQuery) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Type', 'ID', 'User', 'Phone', 'Network', 'Package', 'Amount', 'Status', 'Date']);

            $agentQuery->orderBy('created_at', 'desc')->chunk(500, function($orders) use($file) {
                foreach ($orders as $o) {
                    $type = $o->agent?->role === 'sub_agent' ? 'Sub-Agent' : 'Agent Order';
                    fputcsv($file, [
                        $type,
                        $o->id,
                        $o->agent?->name ?? 'Unknown',
                        $o->beneficiary_number,
                        $o->network_type,
                        $o->package_name ?: $o->package_size,
                        $o->amount,
                        $o->status,
                        $o->created_at?->toDateTimeString()
                    ]);
                }
            });

            $storeQuery->orderBy('created_at', 'desc')->chunk(500, function($orders) use($file) {
                foreach ($orders as $o) {
                    fputcsv($file, [
                        'Storefront',
                        $o->id,
                        $o->customer_name ?? 'Guest',
                        $o->beneficiary_number,
                        $o->network_type,
                        $o->package_name ?: $o->package_size,
                        $o->amount,
                        $o->status,
                        $o->created_at?->toDateTimeString()
                    ]);
                }
            });
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function mount()
    {
        $this->loadCustomerOrders();
        $this->loadAgentDealerOrders();
        $this->computeAndDispatchCounts();
    }

    protected function computeAndDispatchCounts(): void
    {
        try {
            $subQueries = [];
            foreach (['orders', 'agent_orders', 'store_orders'] as $table) {
                $conditions = ['1=1'];
                if ($this->dateFrom) $conditions[] = "created_at >= ?";
                if ($this->dateTo) $conditions[] = "created_at <= ?";
                $subQueries[] = "SELECT status FROM {$table} WHERE " . implode(' AND ', $conditions);
            }

            $bindings = [];
            $dateParams = [];
            if ($this->dateFrom) $dateParams[] = $this->dateFrom;
            if ($this->dateTo) $dateParams[] = $this->dateTo . ' 23:59:59';
            for ($i = 0; $i < count($subQueries); $i++) {
                $bindings = array_merge($bindings, $dateParams);
            }

            $raw = "SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN ('processing','verified') THEN 1 ELSE 0 END) AS processing,
                SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status IN ('failed','cancelled') THEN 1 ELSE 0 END) AS failed
            FROM (" . implode(' UNION ALL ', $subQueries) . ") AS combined";

            $result = \DB::selectOne($raw, $bindings);

            $this->pendingCount = (int) ($result->pending ?? 0);
            $this->processingCount = (int) ($result->processing ?? 0);
            $this->completedCount = (int) ($result->completed ?? 0);
            $this->failedCount = (int) ($result->failed ?? 0);
        } catch (\Throwable $e) {
            report($e);
            $this->pendingCount = $this->processingCount = $this->completedCount = $this->failedCount = 0;
        }

        $this->dispatch('order-counts-updated', counts: [
            'pending' => $this->pendingCount,
            'processing' => $this->processingCount,
            'completed' => $this->completedCount,
            'failed' => $this->failedCount,
        ]);
    }

    public function updatedDateFrom()
    {
        $this->loadCustomerOrders();
        $this->loadAgentDealerOrders();
        $this->computeAndDispatchCounts();
    }

    public function updatedDateTo()
    {
        $this->loadCustomerOrders();
        $this->loadAgentDealerOrders();
        $this->computeAndDispatchCounts();
    }

    public function loadCustomerOrders()
    {
        try {
            $query = Order::latest();
            if ($this->customerStatusFilter) {
                $query->where('status', $this->customerStatusFilter);
            }
            if ($this->dateFrom) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            }
            if ($this->customerSearch) {
                $s = $this->customerSearch;
                $query->where(function ($q) use ($s) {
                    $q->where('id', $s)
                      ->orWhere('momo_number', 'like', "%{$s}%")
                      ->orWhere('beneficiary_number', 'like', "%{$s}%");
                });
            }
            if (!empty($this->customerNetworkFilter)) {
                $query->where('network_type', $this->customerNetworkFilter);
            }
            $this->customerOrders = $query->take(100)->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'customer_name' => 'Customer',
                    'customer_phone' => $o->momo_number,
                    'network_type' => $o->network_type,
                    'package_name' => $o->package_name ?: $this->convertSizeToGb($o->package_size),
                    'package_size_gb' => $o->package_size,
                    'sold_price' => (float) $o->amount,
                    'status' => $o->status,
                    'created_at' => $o->created_at?->toDateTimeString(),
                    'order_ref' => $o->order_reference ?? $o->reference ?? '-',
                    'payment_method' => $o->payment_method ?? '-',
                    'base_price' => (float) $o->amount,
                    'processing_fee' => (float) ($o->processing_fee ?? 0),
                    'your_profit' => 0,
                    'agent_id' => null,
                    'payment_reference' => $o->reference ?? '-',
                    'external_transaction_id' => $o->external_transaction_id ?? '-',
                ])
                ->toArray();
        } catch (\Throwable $e) {
            report($e);
            $this->customerOrders = [];
        }
    }

    public function loadAgentDealerOrders()
    {
        try {
            $agentQuery = AgentOrder::with('agent')
                ->whereDoesntHave('package', fn($q) => $q->where('manual_fulfillment', true))
                ->latest()->take(100);
            $storeQuery = StoreOrder::with('agent')
                ->latest()->take(100);

            if ($this->dateFrom) {
                $agentQuery->whereDate('created_at', '>=', $this->dateFrom);
                $storeQuery->whereDate('created_at', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $agentQuery->whereDate('created_at', '<=', $this->dateTo);
                $storeQuery->whereDate('created_at', '<=', $this->dateTo);
            }

            if (!empty($this->agentNetworkFilter)) {
                $agentQuery->where('network_type', $this->agentNetworkFilter);
                $storeQuery->where('network_type', $this->agentNetworkFilter);
            }

            if ($this->agentStatusFilter) {
                $agentQuery->where('status', $this->agentStatusFilter);
                $storeQuery->where('status', $this->agentStatusFilter);
            }

            if ($this->agentDealerSearch) {
            $s = $this->agentDealerSearch;
            $agentQuery->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('beneficiary_number', 'like', "%{$s}%")
                  ->orWhereHas('agent', fn ($aq) => $aq->where('username', 'like', "%{$s}%"));
            });
            $storeQuery->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('customer_phone', 'like', "%{$s}%")
                  ->orWhere('order_ref', 'like', "%{$s}%")
                  ->orWhereHas('agent', fn ($aq) => $aq->where('username', 'like', "%{$s}%"));
            });
        }

        $agentOrders = $agentQuery->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'type' => ($o->agent?->role ?? 'agent') === 'dealer' ? 'Dealer Order' : 'Agent Order',
                'source_type' => 'agent_order',
                'user_name' => $o->agent?->username ?? 'N/A',
                'user_id' => $o->agent_id,
                'beneficiary' => $o->beneficiary_number,
                'network' => $o->network_type,
                'package' => $o->package_size,
                'amount' => (float) $o->amount,
                'status' => $o->status,
                'created' => $o->created_at?->toDateTimeString(),
                'reference' => '-',
                'payment_method' => $o->payment_method ?? '-',
                'tracking_key' => $o->tracking_key ?? '-',
                'external_txn' => $o->external_transaction_id ?? '-',
                'api_data' => $o->api_response_data,
                'base_price' => (float) $o->amount,
                'processing_fee' => (float) ($o->processing_fee ?? 0),
                'order_ref' => '-',
                'external_transaction_id' => $o->external_transaction_id ?? '-',
                'payment_reference' => '-',
            ]);

        $storeOrders = $storeQuery->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'type' => $o->sub_agent_id ? 'Sub-Agent' : 'Storefront',
                'source_type' => 'store_order',
                'user_name' => $o->agent?->username ?? ($o->SubAgent?->username ?? 'N/A'),
                'user_id' => $o->agent_id,
                'beneficiary' => $o->customer_phone,
                'network' => $o->network_type,
                'package' => $o->package_name ?: ((float) $o->package_size_gb . ' GB'),
                'amount' => (float) $o->sold_price,
                'status' => $o->status,
                'created' => $o->created_at?->toDateTimeString(),
                'reference' => $o->order_ref,
                'payment_method' => $o->payment_method ?? '-',
                'tracking_key' => $o->tracking_id ?? '-',
                'external_txn' => $o->external_transaction_id ?? '-',
                'api_data' => $o->api_response_data,
                'base_price' => (float) $o->base_price,
                'processing_fee' => (float) ($o->processing_fee ?? 0),
                'order_ref' => $o->order_ref,
                'external_transaction_id' => $o->external_transaction_id ?? '-',
                'payment_reference' => $o->payment_reference ?? '-',
            ]);

        $merged = $agentOrders->merge($storeOrders)->sortByDesc('created')->take(200)->values();

        if ($this->agentStatusFilter) {
            $merged = $merged->filter(fn ($o) => $o['status'] === $this->agentStatusFilter)->values();
        }

        $this->agentDealerOrders = $merged->toArray();
        } catch (\Throwable $e) {
            report($e);
            $this->agentDealerOrders = [];
        }
    }

    public function openModal($id, $section)
    {
        if ($section === 'customer') {
            $this->selectedOrder = collect($this->customerOrders)->firstWhere('id', $id) ?? [];
        } else {
            $this->selectedOrder = collect($this->agentDealerOrders)->firstWhere('id', $id) ?? [];
        }
        $this->showModal = true;
        $this->dispatch('modal-opened');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedOrder = [];
        $this->dispatch('modal-closed');
    }

    private function convertSizeToGb($size)
    {
        if (preg_match('/^(\d+)\s*MB$/i', $size, $m)) {
            return ((int) $m[1] / 1024) . ' GB';
        }
        return $size;
    }

    public function selectAllCustomer($checked)
    {
        if ($checked) {
            $this->selectedCustomer = collect($this->customerOrders)->pluck('id')->toArray();
        } else {
            $this->selectedCustomer = [];
        }
    }

    public function selectAllAgentDealer($checked)
    {
        if ($checked) {
            $this->selectedAgentDealer = collect($this->agentDealerOrders)->map(fn ($o) => $o['source_type'] . '-' . $o['id'])->toArray();
        } else {
            $this->selectedAgentDealer = [];
        }
    }

    public function bulkUpdateCustomerStatus($status)
    {
        if (empty($this->selectedCustomer)) {
            $this->dispatch('notify', type: 'warning', message: 'No customer orders selected');
            return;
        }
        $count = count($this->selectedCustomer);
        Order::whereIn('id', $this->selectedCustomer)->update(['status' => $status]);
        $this->selectedCustomer = [];
        $this->loadCustomerOrders();
        $this->computeAndDispatchCounts();
        $this->dispatch('notify', type: 'success', message: $count . ' customer orders updated to ' . $status);
    }

    public function bulkUpdateAgentDealerStatus($status)
    {
        if (empty($this->selectedAgentDealer)) {
            $this->dispatch('notify', type: 'warning', message: 'No agent/dealer orders selected');
            return;
        }

        $total = count($this->selectedAgentDealer);
        $agentOrderIds = [];
        $storeOrderIds = [];

        foreach ($this->selectedAgentDealer as $key) {
            $parts = explode('-', $key);
            $type = $parts[0];
            $id = (int) $parts[1];
            if ($type === 'agent_order') {
                $agentOrderIds[] = $id;
            } elseif ($type === 'store_order') {
                $storeOrderIds[] = $id;
            }
        }

        if ($agentOrderIds) {
            AgentOrder::whereIn('id', $agentOrderIds)->update(['status' => $status]);
        }
        if ($storeOrderIds) {
            StoreOrder::whereIn('id', $storeOrderIds)->update(['status' => $status]);
        }

        $this->selectedAgentDealer = [];
        $this->loadAgentDealerOrders();
        $this->computeAndDispatchCounts();
        $this->dispatch('notify', type: 'success', message: $total . ' agent/dealer orders updated to ' . $status);
    }

    public function render()
    {
        return view('livewire.order-manager');
    }
}
