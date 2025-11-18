<?php

namespace Tests\Unit\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Infrastructure\Services\SimulatedPaymentGateway;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_process_payment()
    {
        Log::spy();
        
        $gateway = new SimulatedPaymentGateway();
        
        $paymentData = [
            'order_id' => 1,
            'amount' => 99.99,
            'currency' => 'USD',
            'customer_email' => 'test@example.com',
        ];

        $result = $gateway->processPayment($paymentData);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('success', $result);
        $this->assertIsBool($result->success);

        if ($result->success) {
            $this->assertObjectHasProperty('payment_reference', $result);
            $this->assertNotEmpty($result->payment_reference);
            $this->assertStringStartsWith('PAY-', $result->payment_reference);
        } else {
            $this->assertObjectHasProperty('failure_reason', $result);
            $this->assertNotEmpty($result->failure_reason);
        }

        Log::shouldHaveReceived('info')
            ->with('Processing payment', $paymentData);
    }

    /** @test */
    public function it_can_process_refund()
    {
        Log::spy();
        
        $gateway = new SimulatedPaymentGateway();
        
        $refundData = [
            'order_id' => 1,
            'amount' => 50.00,
            'payment_reference' => 'PAY-123',
        ];

        $result = $gateway->processRefund($refundData);

        $this->assertIsObject($result);
        $this->assertTrue($result->success);
        $this->assertObjectHasProperty('refund_reference', $result);
        $this->assertStringStartsWith('REF-', $result->refund_reference);

        Log::shouldHaveReceived('info')
            ->with('Processing refund', $refundData);
    }

    /** @test */
    public function it_has_failure_rate_for_payments()
    {
        $gateway = new SimulatedPaymentGateway();
        
        $paymentData = [
            'order_id' => 1,
            'amount' => 99.99,
            'currency' => 'USD',
            'customer_email' => 'test@example.com',
        ];

        $results = [];
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $result = $gateway->processPayment($paymentData);
            $results[] = $result->success;
        }

        $successCount = count(array_filter($results));
        $failureCount = $iterations - $successCount;

        // Should have some failures (around 15% failure rate)
        $this->assertGreaterThan(0, $failureCount);
        $this->assertLessThan($iterations, $failureCount);
    }
}

