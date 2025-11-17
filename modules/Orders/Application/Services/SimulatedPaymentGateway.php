<?php
namespace Modules\Payments\Infrastructure\Services;

use Modules\Payments\Domain\Services\PaymentGatewayInterface;

class SimulatedPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(array $paymentData): object
    {
        \Log::info("Processing payment", $paymentData);

        // Simulate payment processing time
        sleep(2);

        // Simulate random failures for testing (15% failure rate)
        if (rand(1, 100) <= 15) {
            return (object) [
                'success' => false,
                'payment_reference' => '',
                'failure_reason' => 'Payment declined by bank',
                'gateway_response' => ['code' => 'DECLINED', 'message' => 'Insufficient funds']
            ];
        }

        // Simulate successful payment
        $paymentReference = 'PAY-' . strtoupper(uniqid());

        return (object) [
            'success' => true,
            'payment_reference' => $paymentReference,
            'failure_reason' => null,
            'gateway_response' => [
                'code' => 'SUCCESS',
                'message' => 'Payment processed successfully',
                'reference' => $paymentReference,
                'timestamp' => now()->toISOString()
            ]
        ];
    }

    public function processRefund(array $refundData): object
    {
        // Implementation for refunds
        return (object) [
            'success' => true,
            'refund_reference' => 'REF-' . strtoupper(uniqid()),
            'failure_reason' => null
        ];
    }
}
