<?php

namespace Tests\Unit\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Domain\Models\Order;
use Modules\Orders\Domain\Models\OrderItem;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_order()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Test Product',
                    'sku' => 'SKU-001',
                    'quantity' => 2,
                    'unit_price' => 49.99,
                    'total_price' => 99.98,
                ],
            ],
            'customer_details' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_number' => 'ORD-001',
            'customer_id' => 1001,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_have_order_items()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'sku' => 'SKU-001',
            'quantity' => 2,
            'unit_price' => 49.99,
            'total_price' => 99.98,
        ]);

        $this->assertCount(1, $order->orderItems);
        $this->assertEquals(1, $order->orderItems->first()->product_id);
    }

    /** @test */
    public function it_can_check_if_order_can_be_processed()
    {
        $pendingOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $reservedOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-002',
            'status' => 'reserved',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $completedOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-003',
            'status' => 'completed',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $this->assertTrue($pendingOrder->canBeProcessed());
        $this->assertTrue($reservedOrder->canBeProcessed());
        $this->assertFalse($completedOrder->canBeProcessed());
    }

    /** @test */
    public function it_can_check_if_order_is_completed()
    {
        $completedOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $pendingOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-002',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $this->assertTrue($completedOrder->isCompleted());
        $this->assertFalse($pendingOrder->isCompleted());
    }

    /** @test */
    public function it_can_check_if_order_failed()
    {
        $cancelledOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'cancelled',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $failedOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-002',
            'status' => 'payment_failed',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $completedOrder = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-003',
            'status' => 'completed',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [],
        ]);

        $this->assertTrue($cancelledOrder->isFailed());
        $this->assertTrue($failedOrder->isFailed());
        $this->assertFalse($completedOrder->isFailed());
    }

    /** @test */
    public function it_casts_items_to_array()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
            'customer_details' => [],
        ]);

        $this->assertIsArray($order->items);
        $this->assertCount(1, $order->items);
    }

    /** @test */
    public function it_casts_customer_details_to_array()
    {
        $order = Order::create([
            'customer_id' => 1001,
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 99.99,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);

        $this->assertIsArray($order->customer_details);
        $this->assertEquals('John Doe', $order->customer_details['name']);
    }
}

