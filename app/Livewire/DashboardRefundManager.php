<?php

namespace App\Livewire;

use App\Models\AgentOrder;
use App\Models\BalanceHistory;
use App\Models\Order;
use App\Models\StoreOrder;
use App\Models\SubAgent;
use App\Models\SubAgentBalanceHistory;
use App\Models\User;
use App\Services\Payment\PaymentRouter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Computed;

class DashboardRefundManager extends Component
{
    public bool $showPublicModal = false;
    public bool $showAgentModal = false;
    public $agentFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    #[Computed]
    public function filterableAgents()
    {
        $agentIds = AgentOrder::whereIn('status', ['pending', 'processing', 'verified'])->pluck('agent_id');
        $storeAgentIds = StoreOrder::whereIn('status', ['pending', 'processing', 'verified'])->pluck('agent_id');
        
        $allIds = $agentIds->concat($storeAgentIds)->unique();
        
        return User::whereIn('id', $allIds)->get(['id', 'full_name', 'username']);
    }

    #[Computed]
    public function publicOrders()
    {
        $query = Order::whereIn('status', ['pending', 'processing', 'verified'])->latest();
        
        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->get();
    }

    #[Computed]
    public function agentOrders()
    {
        $agentOrdersQuery = AgentOrder::with('agent')->whereIn('status', ['pending', 'processing', 'verified'])->latest();
        $storeOrdersQuery = StoreOrder::with(['agent', 'subAgent'])->whereIn('status', ['pending', 'processing', 'verified'])->latest();

        if (!empty($this->agentFilter)) {
            $agentOrdersQuery->where('agent_id', $this->agentFilter);
            $storeOrdersQuery->where('agent_id', $this->agentFilter);
        }
        
        if (!empty($this->dateFrom)) {
            $agentOrdersQuery->whereDate('created_at', '>=', $this->dateFrom);
            $storeOrdersQuery->whereDate('created_at', '>=', $this->dateFrom);
        }
        
        if (!empty($this->dateTo)) {
            $agentOrdersQuery->whereDate('created_at', '<=', $this->dateTo);
            $storeOrdersQuery->whereDate('created_at', '<=', $this->dateTo);
        }

        $agentOrders = $agentOrdersQuery->get();
        $storeOrders = $storeOrdersQuery->get();
        return $agentOrders->concat($storeOrders)->sortByDesc('created_at')->values();
    }

    #[Computed]
    public function publicTotalAmount()
    {
        return $this->publicOrders()->sum('amount');
    }

    #[Computed]
    public function agentTotalAmount()
    {
        $sum = 0;
        foreach ($this->agentOrders() as $order) {
            $sum += isset($order->sub_agent_id) ? $order->sold_price : $order->amount;
        }
        return $sum;
    }

    public function openPublicModal()
    {
        $this->showPublicModal = true;
    }

    public function closePublicModal()
    {
        $this->showPublicModal = false;
    }

    public function openAgentModal()
    {
        $this->showAgentModal = true;
    }

    public function closeAgentModal()
    {
        $this->showAgentModal = false;
    }

    private function processPublicRefund($order, $paymentRouter)
    {
        DB::beginTransaction();
        try {
            if ($order->payment_method === 'paystack' && $order->external_reference) {
                $gateway = $paymentRouter->gateway('paystack');
                $refundResult = $gateway->refund($order->external_reference, (float)$order->amount);
                if (!$refundResult['success']) {
                    Log::error("Paystack Refund Failed for Order ID: {$order->id}", ['error' => $refundResult['message'] ?? 'Unknown']);
                }
            }

            $apiResponse = is_string($order->api_response_data) ? json_decode($order->api_response_data, true) : ($order->api_response_data ?? []);
            $apiResponse['failure_reason'] = 'Refunded by Admin';
            $apiResponse['refunded_at'] = now()->toDateTimeString();

            $order->update([
                'status' => 'refunded',
                'api_response_data' => $apiResponse
            ]);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Refund Error Public Order ID: {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    public function refundSinglePublicOrder($id)
    {
        $order = Order::find($id);
        if ($order && in_array($order->status, ['pending', 'processing', 'verified'])) {
            $paymentRouter = app(PaymentRouter::class);
            if ($this->processPublicRefund($order, $paymentRouter)) {
                $this->dispatch('order-counts-updated');
                $this->dispatch('notify', type: 'success', message: "Public order #{$order->id} refunded successfully.");
            } else {
                $this->dispatch('notify', type: 'error', message: "Failed to refund order #{$order->id}. Check logs.");
            }
        }
    }

    public function refundPublicOrders()
    {
        $orders = $this->publicOrders();
        $count = 0;
        
        $paymentRouter = app(PaymentRouter::class);

        foreach ($orders as $order) {
            if ($this->processPublicRefund($order, $paymentRouter)) {
                $count++;
            }
        }

        $this->showPublicModal = false;
        $this->dispatch('order-counts-updated');
        $this->dispatch('notify', type: 'success', message: "$count public orders refunded successfully.");
    }

    private function processAgentRefund($order)
    {
        DB::beginTransaction();
        try {
            $user = User::find($order->agent_id);
            if ($user) {
                $user->increment('balance', $order->amount);
                
                BalanceHistory::create([
                    'agent_id' => $user->id,
                    'change_amount' => $order->amount,
                    'balance_after' => $user->balance,
                    'reason' => 'order_refund',
                    'ref_id' => $order->id,
                    'description' => "Refund for Agent Order #{$order->id}"
                ]);
            }
            
            $apiResponse = is_string($order->api_response_data) ? json_decode($order->api_response_data, true) : ($order->api_response_data ?? []);
            $apiResponse['failure_reason'] = 'Refunded by Admin';
            $apiResponse['refunded_at'] = now()->toDateTimeString();

            $order->update([
                'status' => 'refunded',
                'api_response_data' => $apiResponse
            ]);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Refund Error Agent Order ID: {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    private function processStoreRefund($order, $paymentRouter)
    {
        DB::beginTransaction();
        try {
            if ($order->sub_agent_id) {
                $subAgent = SubAgent::find($order->sub_agent_id);
                if ($subAgent) {
                    $subAgent->increment('balance', $order->sold_price);
                    
                    SubAgentBalanceHistory::create([
                        'sub_agent_id' => $subAgent->id,
                        'change_amount' => $order->sold_price,
                        'balance_after' => $subAgent->balance,
                        'reason' => 'order_refund',
                        'ref_id' => $order->id,
                        'description' => "Refund for Store Order #{$order->id}"
                    ]);
                }

                $dealer = User::find($order->agent_id);
                if ($dealer) {
                    $dealer->increment('balance', $order->sold_price);
                }
            } else {
                if ($order->payment_method === 'paystack' && $order->external_reference) { 
                    $gateway = $paymentRouter->forDealerStore($order->agent_id);
                    $gateway->refund($order->external_reference, (float)$order->sold_price); 
                }
            }

            $apiResponse = is_string($order->api_response_data) ? json_decode($order->api_response_data, true) : ($order->api_response_data ?? []);
            $apiResponse['failure_reason'] = 'Refunded by Admin';
            $apiResponse['refunded_at'] = now()->toDateTimeString();

            $order->update([
                'status' => 'refunded',
                'api_response_data' => $apiResponse,
                'failure_reason' => 'Refunded by Admin'
            ]);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Refund Error Store Order ID: {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    public function refundSingleAgentOrder($id, $isStore)
    {
        if ($isStore) {
            $order = StoreOrder::find($id);
            if ($order && in_array($order->status, ['pending', 'processing', 'verified'])) {
                $paymentRouter = app(PaymentRouter::class);
                if ($this->processStoreRefund($order, $paymentRouter)) {
                    $this->dispatch('order-counts-updated');
                    $this->dispatch('notify', type: 'success', message: "Store order #{$order->id} refunded successfully.");
                } else {
                    $this->dispatch('notify', type: 'error', message: "Failed to refund store order #{$order->id}. Check logs.");
                }
            }
        } else {
            $order = AgentOrder::find($id);
            if ($order && in_array($order->status, ['pending', 'processing', 'verified'])) {
                if ($this->processAgentRefund($order)) {
                    $this->dispatch('order-counts-updated');
                    $this->dispatch('notify', type: 'success', message: "Agent order #{$order->id} refunded successfully.");
                } else {
                    $this->dispatch('notify', type: 'error', message: "Failed to refund agent order #{$order->id}. Check logs.");
                }
            }
        }
    }

    public function refundAgentOrders()
    {
        $orders = $this->agentOrders(); // Now properly uses the filtered getter
        $count = 0;
        $paymentRouter = app(PaymentRouter::class);

        foreach ($orders as $order) {
            $success = false;
            if (isset($order->sub_agent_id) || $order instanceof StoreOrder) {
                $success = $this->processStoreRefund($order, $paymentRouter);
            } else {
                $success = $this->processAgentRefund($order);
            }
            if ($success) {
                $count++;
            }
        }

        $this->showAgentModal = false;
        $this->dispatch('order-counts-updated');
        $this->dispatch('notify', type: 'success', message: "$count agent/store orders refunded successfully.");
    }

    public function render()
    {
        return view('livewire.dashboard-refund-manager');
    }
}
