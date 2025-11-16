<?php
namespace Modules\Orders\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Domain\Events\PaymentProcessed;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Modules\Orders\Domain\ValueObjects\OrderStatus;
use Modules\Orders\Domain\Services\PaymentGatewayInterface;

class SimulatePaymentUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentGatewayInterface $paymentGateway
    ) {}

    public function execute(string $orderId): void
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \Exception("Order not found: {$orderId}");
        }

        DB::transaction(function () use ($order) {
            // Update order status
            $order->update([
                'status' => OrderStatus::PROCESSING_PAYMENT->value
            ]);

            try {
                // Simulate payment processing
                $paymentResult = $this->paymentGateway->processPayment([
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'customer_email' => $order->customer_details['email'] ?? '',
                ]);

                if ($paymentResult->success) {
                    $order->update([
                        'status' => OrderStatus::PAYMENT_SUCCEEDED->value,
                        'paid_at' => now()
                    ]);
                } else {
                    $order->update([
                        'status' => OrderStatus::PAYMENT_FAILED->value,
                        'failed_at' => now(),
                        'failure_reason' => $paymentResult->failure_reason
                    ]);
                }

                // Dispatch payment processed event
                event(new PaymentProcessed(
                    $order,
                    $paymentResult->success,
                    $paymentResult->payment_reference,
                    $paymentResult->failure_reason
                ));

            } catch (\Exception $e) {
                $order->update([
                    'status' => OrderStatus::PAYMENT_FAILED->value,
                    'failed_at' => now(),
                    'failure_reason' => $e->getMessage()
                ]);

                event(new PaymentProcessed(
                    $order,
                    false,
                    '',
                    $e->getMessage()
                ));

                throw $e;
            }
        });
    }
}
