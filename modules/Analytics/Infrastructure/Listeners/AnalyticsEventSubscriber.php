<?php
namespace Modules\Analytics\Infrastructure\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Analytics\Application\UseCases\UpdateDailyKpisUseCase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Events\OrderFailed;
use Modules\Refunds\Domain\Events\RefundProcessed;
use Modules\Refunds\Domain\Events\RefundFailed;

class AnalyticsEventSubscriber
{
    public function __construct(
        private UpdateDailyKpisUseCase $updateKpisUseCase,
        private UpdateLeaderboardUseCase $updateLeaderboardUseCase
    ) {}

    public function handleOrderCompleted(OrderCompleted $event): void
    {
        $order = $event->order;
        $date = $order->created_at->format('Y-m-d');

        // Update KPIs (track unique customer)
        $this->updateKpisUseCase->execute($date, (float) $order->total_amount, true, (string) $order->customer_id);

        // Update leaderboard
        $this->updateLeaderboardUseCase->execute(
            (string) $order->customer_id,
            $date,
            (float) $order->total_amount
        );
    }

    public function handleOrderFailed(OrderFailed $event): void
    {
        $order = $event->order;
        $date = $order->created_at->format('Y-m-d');

        // Update KPIs (failed order)
        $this->updateKpisUseCase->execute($date, 0, false);
    }

    public function handleRefundProcessed(RefundProcessed $event): void
    {
        $refund = $event->refund;
        $order = $event->order;
        
        // Use refund processed date, fallback to order date
        $date = $refund->processed_at 
            ? $refund->processed_at->format('Y-m-d')
            : $order->created_at->format('Y-m-d');

        // Update KPIs for refund (decrement revenue, increment refund amount)
        $this->updateKpisUseCase->handleRefund($date, (float) $refund->amount, true);

        // Update leaderboard for refund (decrement customer spending)
        $this->updateLeaderboardUseCase->handleRefund(
            (string) $order->customer_id,
            $date,
            (float) $refund->amount
        );
    }

    public function handleRefundFailed(RefundFailed $event): void
    {
        // Log failed refund but don't update KPIs
        \Log::warning("Refund failed", [
            'refund_id' => $event->refund->id,
            'order_id' => $event->order->id,
            'reason' => $event->reason,
        ]);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            OrderCompleted::class,
            [AnalyticsEventSubscriber::class, 'handleOrderCompleted']
        );

        $events->listen(
            OrderFailed::class,
            [AnalyticsEventSubscriber::class, 'handleOrderFailed']
        );

        $events->listen(
            RefundProcessed::class,
            [AnalyticsEventSubscriber::class, 'handleRefundProcessed']
        );

        $events->listen(
            RefundFailed::class,
            [AnalyticsEventSubscriber::class, 'handleRefundFailed']
        );
    }
}
