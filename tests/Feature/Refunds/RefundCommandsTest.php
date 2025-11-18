<?php

namespace Tests\Feature\Refunds;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Orders\Domain\Models\Order;
use Modules\Refunds\Application\Jobs\ProcessRefundJob;
use Modules\Refunds\Domain\Models\Refund;
use Tests\TestCase;

class RefundCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_processes_full_refund_via_command()
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

        $this->artisan("refunds:process {$order->id} --type=full --reason='Customer requested'")
            ->expectsOutputToContain("Processing full refund for order #{$order->id}...")
            ->expectsOutputToContain('Refund job queued successfully.')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessRefundJob::class, function ($job) use ($order) {
            return $job->refundRequest->orderId === $order->id &&
                   $job->refundRequest->type === 'full';
        });
    }

    public function test_it_processes_partial_refund_via_command()
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

        $this->artisan("refunds:process {$order->id} --type=partial --amount=50.00")
            ->expectsOutputToContain("Processing partial refund for order #{$order->id}...")
            ->assertExitCode(0);

        Queue::assertPushed(ProcessRefundJob::class, function ($job) use ($order) {
            return $job->refundRequest->orderId === $order->id &&
                   $job->refundRequest->type === 'partial' &&
                   $job->refundRequest->amount === 50.00;
        });
    }

    public function test_it_validates_amount_required_for_partial_refund()
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

        $this->artisan("refunds:process {$order->id} --type=partial")
            ->expectsOutputToContain('Amount is required for partial refunds')
            ->assertExitCode(1);
    }

    public function test_it_validates_type_must_be_full_or_partial()
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

        $this->artisan("refunds:process {$order->id} --type=invalid")
            ->expectsOutputToContain('Type must be either "full" or "partial"')
            ->assertExitCode(1);
    }

    public function test_it_accepts_custom_refund_id_for_idempotency()
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

        $this->artisan("refunds:process {$order->id} --refund-id=REF-UNIQUE-123")
            ->assertExitCode(0);

        Queue::assertPushed(ProcessRefundJob::class, function ($job) {
            return $job->refundRequest->refundId === 'REF-UNIQUE-123';
        });
    }

    public function test_it_lists_all_refunds()
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

        $this->artisan('refunds:list')
            ->expectsOutputToContain('Found 2 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_refunds_by_order_id()
    {
        $order1 = Order::create([
            'customer_id' => 456,
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 100.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        $order2 = Order::create([
            'customer_id' => 789,
            'order_number' => 'ORD-002',
            'status' => 'completed',
            'total_amount' => 200.00,
            'currency' => 'USD',
            'items' => [],
            'customer_details' => ['name' => 'Test', 'email' => 'test@example.com'],
        ]);

        Refund::create([
            'order_id' => $order1->id,
            'customer_id' => 456,
            'refund_id' => 'REF-001',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        Refund::create([
            'order_id' => $order2->id,
            'customer_id' => 789,
            'refund_id' => 'REF-002',
            'amount' => 200.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $this->artisan("refunds:list --order-id={$order1->id}")
            ->expectsOutputToContain('Found 1 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_refunds_by_customer_id()
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
            'customer_id' => 789,
            'refund_id' => 'REF-002',
            'amount' => 200.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $this->artisan('refunds:list --customer-id=456')
            ->expectsOutputToContain('Found 1 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_refunds_by_status()
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
            'status' => 'pending',
        ]);

        $this->artisan('refunds:list --status=completed')
            ->expectsOutputToContain('Found 1 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_filters_refunds_by_type()
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

        $this->artisan('refunds:list --type=partial')
            ->expectsOutputToContain('Found 1 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_combines_multiple_filters()
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
            'status' => 'pending',
        ]);

        $this->artisan("refunds:list --order-id={$order->id} --type=full --status=completed")
            ->expectsOutputToContain('Found 1 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_respects_limit_option()
    {
        // Create 10 refunds
        for ($i = 1; $i <= 10; $i++) {
            Refund::create([
                'order_id' => $i,
                'customer_id' => 456,
                'refund_id' => "REF-{$i}",
                'amount' => 100.00,
                'type' => 'full',
                'status' => 'completed',
            ]);
        }

        $this->artisan('refunds:list --limit=5')
            ->expectsOutputToContain('Found 5 refund(s):')
            ->assertExitCode(0);
    }

    public function test_it_shows_message_when_no_refunds_found()
    {
        $this->artisan('refunds:list')
            ->expectsOutput('No refunds found.')
            ->assertExitCode(0);
    }

    public function test_it_shows_message_when_filter_returns_no_results()
    {
        Refund::create([
            'order_id' => 1,
            'customer_id' => 456,
            'refund_id' => 'REF-001',
            'amount' => 100.00,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $this->artisan('refunds:list --order-id=999')
            ->expectsOutput('No refunds found.')
            ->assertExitCode(0);
    }
}

