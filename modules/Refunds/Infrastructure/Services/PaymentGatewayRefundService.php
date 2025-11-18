<?php

namespace Modules\Refunds\Infrastructure\Services;

use Illuminate\Support\Facades\Log;

class PaymentGatewayRefundService
{
    /**
     * Simulate refund processing through payment gateway
     */
    public function processRefund(float $amount, string $paymentReference): array
    {
        // Simulate API call delay
        usleep(100000); // 100ms

        // Simulate 95% success rate
        $success = rand(1, 100) <= 95;

        if ($success) {
            $refundReference = 'REF-' . strtoupper(uniqid());
            
            Log::info("Refund processed successfully", [
                'amount' => $amount,
                'payment_reference' => $paymentReference,
                'refund_reference' => $refundReference,
            ]);

            return [
                'success' => true,
                'refund_reference' => $refundReference,
                'processed_at' => now()->toISOString(),
            ];
        }

        $failureReasons = [
            'Payment gateway timeout',
            'Insufficient funds in merchant account',
            'Refund limit exceeded',
            'Payment gateway error',
        ];

        $reason = fake()->randomElement($failureReasons);

        Log::warning("Refund failed", [
            'amount' => $amount,
            'payment_reference' => $paymentReference,
            'reason' => $reason,
        ]);

        return [
            'success' => false,
            'reason' => $reason,
        ];
    }
}

