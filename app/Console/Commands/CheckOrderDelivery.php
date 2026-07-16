<?php

namespace App\Console\Commands;

use App\Models\AgentOrder;
use App\Models\Order;
use App\Models\StoreOrder;
use App\Services\DataBundle\DataBundleRouter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class CheckOrderDelivery extends Command
{
    protected $signature = 'orders:check-delivery';
    protected $description = 'Poll the active provider for delivery status of processing orders';

    public function handle(DataBundleRouter $router): int
    {
        if (!$router->isCredentialsConfigured()) {
            $this->warn('Provider credentials not configured — skipping');
            return Command::FAILURE;
        }

        $checked = 0;
        $updated = 0;
        $failed = 0;
        $maxPerRun = 30;

        $models = [
            'AgentOrder' => AgentOrder::class,
            'StoreOrder' => StoreOrder::class,
            'Order' => Order::class,
        ];

        foreach ($models as $label => $modelClass) {
            $orders = $modelClass::where('status', 'processing')
                ->whereNotNull('external_transaction_id')
                ->where('external_transaction_id', '!=', '')
                ->orderBy('id')
                ->take($maxPerRun)
                ->get();

            foreach ($orders as $order) {
                $checked++;
                $result = $router->checkOrderStatus($order->external_transaction_id, $order->network_type);

                if (!empty($result['is_delivered'])) {
                    $order->update(['status' => 'completed']);
                    $updated++;
                    $this->line("{$label} #{$order->id} → completed");
                } elseif (!empty($result['is_failed'])) {
                    $order->update(['status' => 'failed']);
                    $failed++;
                    $this->line("{$label} #{$order->id} → failed");
                } elseif (!empty($result['error'])) {
                    Log::warning("{$label} #{$order->id} check failed", [
                        'error' => $result['error'],
                        'external_txn' => $order->external_transaction_id,
                    ]);
                }
            }
        }

        $this->info("Checked {$checked} orders, updated {$updated} to completed, {$failed} marked failed");

        return Command::SUCCESS;
    }
}
