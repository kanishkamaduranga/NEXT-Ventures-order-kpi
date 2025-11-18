<?php

namespace Tests\Unit\Refunds;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Domain\Models\Order;
use Modules\Refunds\Domain\Models\Refund;
use Tests\TestCase;

class RefundModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_refund()
    {
        $order = Order::create([
            'customer_id' => 456,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 100.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
            'reason' => 'Customer requested refund',
        ]);

        $this->assertNotNull($refund->id);
        $this->assertEquals($order->id, $refund->order_id);
        $this->assertEquals(456, $refund->customer_id);
        $this->assertEquals('REF-123', $refund->refund_id);
        $this->assertEquals(100.00, $refund->amount);
        $this->assertEquals('full', $refund->type);
        $this->assertEquals('pending', $refund->status);
    }

    public function test_it_checks_pending_status()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $this->assertTrue($refund->isPending());
        $this->assertFalse($refund->isProcessing());
        $this->assertFalse($refund->isCompleted());
        $this->assertFalse($refund->isFailed());
    }

    public function test_it_checks_processing_status()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'processing',
        ]);

        $this->assertTrue($refund->isProcessing());
        $this->assertFalse($refund->isPending());
        $this->assertFalse($refund->isCompleted());
        $this->assertFalse($refund->isFailed());
    }

    public function test_it_checks_completed_status()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $this->assertTrue($refund->isCompleted());
        $this->assertFalse($refund->isPending());
        $this->assertFalse($refund->isProcessing());
        $this->assertFalse($refund->isFailed());
    }

    public function test_it_checks_failed_status()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'failed',
        ]);

        $this->assertTrue($refund->isFailed());
        $this->assertFalse($refund->isPending());
        $this->assertFalse($refund->isProcessing());
        $this->assertFalse($refund->isCompleted());
    }

    public function test_it_checks_full_refund_type()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $this->assertTrue($refund->isFullRefund());
        $this->assertFalse($refund->isPartialRefund());
    }

    public function test_it_checks_partial_refund_type()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 50.00,
            'type' => 'partial',
            'status' => 'pending',
        ]);

        $this->assertTrue($refund->isPartialRefund());
        $this->assertFalse($refund->isFullRefund());
    }

    public function test_it_casts_amount_to_decimal()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => '99.99',
            'type' => 'full',
            'status' => 'pending',
        ]);

        // Decimal cast returns a string representation
        $this->assertIsString($refund->amount);
        $this->assertEquals('99.99', $refund->amount);
    }

    public function test_it_casts_processed_at_to_datetime()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $refund->processed_at);
    }

    public function test_it_has_order_relationship()
    {
        $order = Order::create([
            'customer_id' => 456,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 100.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Order::class, $refund->order);
        $this->assertEquals($order->id, $refund->order->id);
    }
}

