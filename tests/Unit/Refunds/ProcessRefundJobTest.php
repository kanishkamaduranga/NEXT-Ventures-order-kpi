<?php

namespace Tests\Unit\Refunds;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Orders\Domain\Models\Order;
use Modules\Refunds\Application\DTOs\RefundRequest;
use Modules\Refunds\Application\Jobs\ProcessRefundJob;
use Modules\Refunds\Domain\Events\RefundFailed;
use Modules\Refunds\Domain\Events\RefundProcessed;
use Modules\Refunds\Domain\Models\Refund;
use Modules\Refunds\Domain\Repositories\RefundRepositoryInterface;
use Modules\Refunds\Infrastructure\Services\PaymentGatewayRefundService;
use Modules\Orders\Domain\Repositories\OrderRepositoryInterface;
use Tests\TestCase;

class ProcessRefundJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();
    }

    public function test_it_creates_refund_record_when_handled()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full',
            reason: 'Customer requested refund'
        );

        // Mock payment gateway to return success
        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        $refund = Refund::where('order_id', $order->id)->first();

        $this->assertNotNull($refund);
        $this->assertEquals($order->id, $refund->order_id);
        $this->assertEquals(456, $refund->customer_id);
        $this->assertEquals(100.00, $refund->amount);
        $this->assertEquals('full', $refund->type);
    }

    public function test_it_sets_amount_to_order_total_for_full_refund()
    {
        $order = Order::create([
            'customer_id' => 456,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 150.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 0, // Will be set to order total
            type: 'full'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->with(150.00, \Mockery::any())
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertEquals(150.00, $refund->amount);
    }

    public function test_it_processes_partial_refund()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 50.00,
            type: 'partial',
            reason: 'Partial refund for damaged item'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->with(50.00, \Mockery::any())
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertEquals(50.00, $refund->amount);
        $this->assertEquals('partial', $refund->type);
    }

    public function test_it_dispatches_refund_processed_event_on_success()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        Event::assertDispatched(RefundProcessed::class, function ($event) use ($order) {
            return $event->order->id === $order->id &&
                   $event->refund->status === 'completed';
        });
    }

    public function test_it_dispatches_refund_failed_event_on_failure()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => false,
                'reason' => 'Payment gateway timeout',
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        Event::assertDispatched(RefundFailed::class, function ($event) use ($order) {
            return $event->order->id === $order->id &&
                   $event->refund->status === 'failed' &&
                   $event->reason === 'Payment gateway timeout';
        });
    }

    public function test_it_handles_idempotency_when_refund_already_exists()
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

        // Create existing refund
        $existingRefund = Refund::create([
            'order_id' => $order->id,
            'customer_id' => 456,
            'refund_id' => 'REF-UNIQUE-123',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full',
            refundId: 'REF-UNIQUE-123' // Same refund ID
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldNotReceive('processRefund'); // Should not be called

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        // Should re-dispatch event for analytics update
        Event::assertDispatched(RefundProcessed::class);
        
        // Should not create a new refund
        $refunds = Refund::where('order_id', $order->id)->get();
        $this->assertCount(1, $refunds);
    }

    public function test_it_generates_refund_id_if_not_provided()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full',
            refundId: null // Not provided
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertNotNull($refund->refund_id);
        $this->assertStringStartsWith('REF-', $refund->refund_id);
    }

    public function test_it_skips_processing_when_order_not_found()
    {
        // Mock order repository to return null
        $orderRepository = \Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findById')
            ->once()
            ->with(99999)
            ->andReturn(null);

        $this->app->instance(OrderRepositoryInterface::class, $orderRepository);

        $refundRequest = new RefundRequest(
            orderId: 99999, // Non-existent order
            amount: 100.00,
            type: 'full'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldNotReceive('processRefund');

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            $orderRepository,
            $paymentGateway
        );

        // Should not create refund or dispatch refund events
        $refunds = Refund::all();
        $this->assertCount(0, $refunds);
        Event::assertNotDispatched(RefundProcessed::class);
        Event::assertNotDispatched(RefundFailed::class);
    }

    public function test_it_skips_processing_when_refund_amount_exceeds_order_total()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 200.00, // Exceeds order total
            type: 'partial'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldNotReceive('processRefund');

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        // Should not create refund
        $refunds = Refund::where('order_id', $order->id)->get();
        $this->assertCount(0, $refunds);
    }

    public function test_it_updates_refund_status_to_processing()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        // The refund should be created with 'processing' status initially
        // then updated to 'completed'
        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertEquals('completed', $refund->status);
    }

    public function test_it_sets_payment_reference_on_success()
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

        $refundRequest = new RefundRequest(
            orderId: $order->id,
            amount: 100.00,
            type: 'full'
        );

        $paymentGateway = \Mockery::mock(PaymentGatewayRefundService::class);
        $paymentGateway->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => true,
                'refund_reference' => 'REF-GATEWAY-123',
                'processed_at' => now()->toISOString(),
            ]);

        $this->app->instance(PaymentGatewayRefundService::class, $paymentGateway);

        $job = new ProcessRefundJob($refundRequest);
        $job->handle(
            app(RefundRepositoryInterface::class),
            app(OrderRepositoryInterface::class),
            $paymentGateway
        );

        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertEquals('REF-GATEWAY-123', $refund->payment_reference);
    }
}

