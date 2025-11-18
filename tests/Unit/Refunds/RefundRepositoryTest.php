<?php

namespace Tests\Unit\Refunds;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Domain\Models\Order;
use Modules\Refunds\Domain\Models\Refund;
use Modules\Refunds\Domain\Repositories\RefundRepositoryInterface;
use Tests\TestCase;

class RefundRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(RefundRepositoryInterface::class);
    }

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

        $data = [
            'order_id' => $order->id,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ];

        $refund = $this->repository->create($data);

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertNotNull($refund->id);
        $this->assertEquals('REF-123', $refund->refund_id);
    }

    public function test_it_can_find_refund_by_id()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $found = $this->repository->findById($refund->id);

        $this->assertInstanceOf(Refund::class, $found);
        $this->assertEquals($refund->id, $found->id);
        $this->assertEquals('REF-123', $found->refund_id);
    }

    public function test_it_returns_null_when_refund_not_found()
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    public function test_it_can_find_refund_by_refund_id()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-UNIQUE-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $found = $this->repository->findByRefundId('REF-UNIQUE-123');

        $this->assertInstanceOf(Refund::class, $found);
        $this->assertEquals($refund->id, $found->id);
        $this->assertEquals('REF-UNIQUE-123', $found->refund_id);
    }

    public function test_it_returns_null_when_refund_id_not_found()
    {
        $found = $this->repository->findByRefundId('NON-EXISTENT');

        $this->assertNull($found);
    }

    public function test_it_can_update_refund()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $result = $this->repository->update($refund->id, [
            'payment_reference' => 'PAY-REF-456',
            'reason' => 'Updated reason',
        ]);

        $this->assertTrue($result);
        $refund->refresh();
        $this->assertEquals('PAY-REF-456', $refund->payment_reference);
        $this->assertEquals('Updated reason', $refund->reason);
    }

    public function test_it_returns_false_when_updating_non_existent_refund()
    {
        $result = $this->repository->update(99999, ['status' => 'completed']);

        $this->assertFalse($result);
    }

    public function test_it_can_update_status()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'pending',
        ]);

        $result = $this->repository->updateStatus($refund->id, 'processing');

        $this->assertTrue($result);
        $refund->refresh();
        $this->assertEquals('processing', $refund->status);
    }

    public function test_it_sets_processed_at_when_status_is_completed()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'processing',
        ]);

        $result = $this->repository->updateStatus($refund->id, 'completed');

        $this->assertTrue($result);
        $refund->refresh();
        $this->assertEquals('completed', $refund->status);
        $this->assertNotNull($refund->processed_at);
    }

    public function test_it_sets_failure_reason_when_provided()
    {
        $refund = Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'processing',
        ]);

        $result = $this->repository->updateStatus($refund->id, 'failed', 'Payment gateway timeout');

        $this->assertTrue($result);
        $refund->refresh();
        $this->assertEquals('failed', $refund->status);
        $this->assertEquals('Payment gateway timeout', $refund->failure_reason);
    }

    public function test_it_can_find_refunds_by_order_id()
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

        Refund::create([
            'order_id' => $order->id,
            'customer_id' => 456,
            'refund_id' => 'REF-001',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        Refund::create([
            'order_id' => $order->id,
            'customer_id' => 456,
            'refund_id' => 'REF-002',
            'amount' => 50.00,
            'type' => 'partial',
            'status' => 'completed',
        ]);

        // Create refund for different order
        Refund::create([
            'order_id' => 999,
            'customer_id' => 789,
            'refund_id' => 'REF-003',
            'amount' => 200.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $refunds = $this->repository->findByOrderId($order->id);

        $this->assertIsArray($refunds);
        $this->assertCount(2, $refunds);
        $this->assertEquals($order->id, $refunds[0]->order_id);
        $this->assertEquals($order->id, $refunds[1]->order_id);
    }

    public function test_it_returns_empty_array_when_no_refunds_for_order()
    {
        $refunds = $this->repository->findByOrderId(99999);

        $this->assertIsArray($refunds);
        $this->assertEmpty($refunds);
    }

    public function test_it_can_find_refunds_by_customer_id()
    {
        Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-001',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        Refund::create([
            'order_id' => 2,
            'customer_id' => 456,
            'refund_id' => 'REF-002',
            'amount' => 50.00,
            'type' => 'partial',
            'status' => 'completed',
        ]);

        // Create refund for different customer
        Refund::create([
            'order_id' => 3,
            'customer_id' => 789,
            'refund_id' => 'REF-003',
            'amount' => 200.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $refunds = $this->repository->findByCustomerId(456);

        $this->assertIsArray($refunds);
        $this->assertCount(2, $refunds);
        $this->assertEquals(456, $refunds[0]->customer_id);
        $this->assertEquals(456, $refunds[1]->customer_id);
    }

    public function test_it_returns_empty_array_when_no_refunds_for_customer()
    {
        $refunds = $this->repository->findByCustomerId(99999);

        $this->assertIsArray($refunds);
        $this->assertEmpty($refunds);
    }
}

