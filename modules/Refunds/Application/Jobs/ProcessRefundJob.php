<?php

namespace Modules\Refunds\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Refunds\Application\DTOs\RefundRequest;
use Modules\Refunds\Domain\Events\RefundFailed;
use Modules\Refunds\Domain\Events\RefundProcessed;
use Modules\Refunds\Domain\Repositories\RefundRepositoryInterface;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Modules\Refunds\Infrastructure\Services\PaymentGatewayRefundService;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public RefundRequest $refundRequest
    ) {
        $this->onQueue('refunds');
    }

    public function handle(
        RefundRepositoryInterface $refundRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentGatewayRefundService $paymentGateway
    ): void {
        $order = $orderRepository->findById($this->refundRequest->orderId);

        if (!$order) {
            Log::error("Order not found for refund", [
                'order_id' => $this->refundRequest->orderId,
            ]);
            return;
        }

        // Idempotency check: If refund_id is provided, check if refund already exists
        if ($this->refundRequest->refundId) {
            $existingRefund = $refundRepository->findByRefundId($this->refundRequest->refundId);
            
            if ($existingRefund) {
                Log::info("Refund already processed (idempotency check)", [
                    'refund_id' => $this->refundRequest->refundId,
                    'existing_refund_id' => $existingRefund->id,
                    'status' => $existingRefund->status,
                ]);

                // If already completed, dispatch the event again for analytics update
                if ($existingRefund->isCompleted()) {
                    event(new RefundProcessed($existingRefund, $order));
                }

                return; // Idempotent: don't process again
            }
        }

        // Generate refund_id if not provided
        $refundId = $this->refundRequest->refundId ?? 'REF-' . strtoupper(uniqid());

        // Validate refund amount
        if ($this->refundRequest->type === 'full') {
            $this->refundRequest->amount = (float) $order->total_amount;
        } elseif ($this->refundRequest->amount > (float) $order->total_amount) {
            Log::error("Refund amount exceeds order total", [
                'order_id' => $order->id,
                'refund_amount' => $this->refundRequest->amount,
                'order_total' => $order->total_amount,
            ]);
            return;
        }

        // Create refund record
        $refund = $refundRepository->create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'refund_id' => $refundId,
            'amount' => $this->refundRequest->amount,
            'type' => $this->refundRequest->type,
            'status' => 'processing',
            'reason' => $this->refundRequest->reason,
        ]);

        // Update status to processing
        $refundRepository->updateStatus($refund->id, 'processing');

        // Process refund through payment gateway
        $paymentReference = $order->customer_details['payment_reference'] ?? 'PAY-' . $order->id;
        $result = $paymentGateway->processRefund($this->refundRequest->amount, $paymentReference);

        if ($result['success']) {
            // Update refund record
            $refundRepository->update($refund->id, [
                'payment_reference' => $result['refund_reference'],
            ]);
            $refundRepository->updateStatus($refund->id, 'completed');

            // Refresh refund model
            $refund->refresh();

            // Dispatch event for analytics update
            event(new RefundProcessed($refund, $order));

            Log::info("Refund processed successfully", [
                'refund_id' => $refund->id,
                'refund_ref_id' => $refundId,
                'order_id' => $order->id,
                'amount' => $this->refundRequest->amount,
            ]);
        } else {
            // Update refund record with failure
            $refundRepository->updateStatus($refund->id, 'failed', $result['reason']);

            // Refresh refund model
            $refund->refresh();

            // Dispatch event
            event(new RefundFailed($refund, $order, $result['reason']));

            Log::error("Refund processing failed", [
                'refund_id' => $refund->id,
                'refund_ref_id' => $refundId,
                'order_id' => $order->id,
                'reason' => $result['reason'],
            ]);
        }
    }
}

