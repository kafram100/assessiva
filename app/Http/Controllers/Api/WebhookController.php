<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentOrder;
use App\Models\Order;
use App\Models\StoreOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handlePortalGhWebhook(Request $request)
    {
        $payload = $request->all();
        
        // Log the incoming webhook payload for debugging
        Log::info('PortalGH Webhook Received', $payload);

        // Try to find a reference/ID from common webhook keys
        $reference = $request->input('reference') 
            ?? $request->input('order_id') 
            ?? $request->input('trans_id') 
            ?? $request->input('transaction_id')
            ?? $request->input('external_transaction_id');
            
        // Try to find the status
        $rawStatus = strtolower((string) ($request->input('status') ?? $request->input('order_status') ?? ''));

        if (!$reference || !$rawStatus) {
            Log::warning('PortalGH Webhook missing reference or status', $payload);
            return response()->json(['success' => false, 'message' => 'Missing reference or status'], 400);
        }

        // Determine mapped status
        $newStatus = null;
        if (in_array($rawStatus, ['delivered', 'completed', 'success', 'fulfilled', 'done'])) {
            $newStatus = 'completed';
        } elseif (in_array($rawStatus, ['failed', 'cancelled', 'rejected', 'error', 'refunded'])) {
            $newStatus = 'failed';
        }

        if (!$newStatus) {
            Log::info("PortalGH Webhook unknown status: {$rawStatus}", $payload);
            return response()->json(['success' => true, 'message' => 'Ignored unknown status: ' . $rawStatus]);
        }

        $updated = false;

        // Models to check
        $models = [
            'AgentOrder' => AgentOrder::class,
            'StoreOrder' => StoreOrder::class,
            'Order' => Order::class,
        ];

        foreach ($models as $type => $modelClass) {
            // Search by external_transaction_id or reference/order_ref/tracking_key
            $order = $modelClass::where('external_transaction_id', $reference)
                ->orWhere(function ($query) use ($reference, $type) {
                    if ($type === 'Order') {
                        $query->where('reference', $reference)
                              ->orWhere('order_reference', $reference)
                              ->orWhere('tracking_key', $reference);
                    } elseif ($type === 'StoreOrder') {
                        $query->where('order_ref', $reference)
                              ->orWhere('tracking_id', $reference)
                              ->orWhere('payment_reference', $reference);
                    } elseif ($type === 'AgentOrder') {
                        $query->where('tracking_key', $reference);
                    }
                })
                ->first();

            if ($order) {
                // If it's still processing or verified or pending, update it.
                if (in_array($order->status, ['processing', 'pending', 'verified'])) {
                    $order->update(['status' => $newStatus]);
                    Log::info("PortalGH Webhook updated {$type} #{$order->id} to {$newStatus}");
                    $updated = true;
                } else {
                    Log::info("PortalGH Webhook ignored {$type} #{$order->id} because status is already {$order->status}");
                    $updated = true;
                }
                break;
            }
        }

        if (!$updated) {
            Log::warning("PortalGH Webhook could not find order for reference: {$reference}");
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Webhook processed successfully']);
    }
}
