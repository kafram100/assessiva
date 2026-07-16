<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ApiSubmissionRegistry;
use App\Services\DataBundle\DataBundleRouter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDataBundleOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public Order $order) {}

    public function handle(DataBundleRouter $router): void
    {
        $this->order->refresh();

        if (!in_array($this->order->status, ['payment_confirmed', 'completed'])) {
            return;
        }

        $network = $router->normalizeNetwork($this->order->network_type);
        $phone = $router->validatePhoneNumber($this->order->beneficiary_number);
        $capacityMb = $router->convertPackageSize($this->order->package_size);

        if (!$phone || $capacityMb <= 0) {
            $this->failOrder('Invalid phone number or package size');
            return;
        }

        $this->updateStatus('api_processing', 'Submitting to external API');

        $submissionKey = 'order:' . $this->order->id;
        $response = $router->purchaseData(
            msisdn: $phone,
            network: $network,
            capacityMb: $capacityMb,
            paymentMethod: 'public_paid',
            localOrderId: $this->order->id,
            submissionKey: $submissionKey,
        );

        $this->recordSubmission($response, $submissionKey);

        if (!empty($response['success']) || !empty($response['duplicate_prevented'])) {
            $this->order->update([
                'status' => 'processing',
                'base_cost' => $response['base_cost'] ?? null,
                'external_transaction_id' => $response['external_transaction_id'] ?? null,
                'external_reference' => $response['external_reference'] ?? null,
                'api_response_data' => $response['data'] ?? null,
                'status_updated_at' => now(),
            ]);

            $this->updateStatus('processing', 'Data bundle submitted for delivery, awaiting confirmation');
        } else {
            $error = $response['error'] ?? 'API submission failed';
            if ($this->attempts() >= $this->tries) {
                $this->failOrder($error);
            } else {
                $this->updateStatus('payment_confirmed', "API submission failed, will retry: {$error}");
                $this->release($this->backoff[$this->attempts() - 1] ?? 300);
            }
        }
    }

    protected function updateStatus(string $status, string $reason): void
    {
        $old = $this->order->status;
        $this->order->update(['status' => $status, 'status_updated_at' => now()]);

        OrderStatusHistory::create([
            'order_id' => $this->order->id,
            'old_status' => $old,
            'new_status' => $status,
            'change_reason' => $reason,
            'changed_by' => 'system',
        ]);
    }

    protected function failOrder(string $error): void
    {
        $this->updateStatus('failed', $error);

        $this->order->update(['api_response_data' => ['error' => $error]]);
    }

    protected function recordSubmission(array $response, string $submissionKey): void
    {
        ApiSubmissionRegistry::updateOrCreate(
            ['order_id' => $this->order->id],
            [
                'network_type' => $this->order->network_type,
                'reference' => $this->order->order_reference,
                'external_transaction_id' => $response['external_transaction_id'] ?? null,
                'api_response' => $response['data'] ?? $response,
                'status' => !empty($response['success']) ? 'delivered' : 'failed',
                'completed_at' => !empty($response['success']) ? now() : null,
            ]
        );
    }
}
