<?php
namespace Modules\Analytics\Infrastructure\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Analytics\Application\UseCases\UpdateDailyKpisUseCase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Events\OrderFailed;

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

    // Refund handling can be added when RefundProcessed event is implemented
    // public function handleRefundProcessed(RefundProcessed $event): void
    // {
    //     $refund = $event->refund;
    //     $order = $event->order;
    //     $date = $order->created_at->format('Y-m-d');
    //
    //     // Update KPIs for refund
    //     $this->updateKpisUseCase->handleRefund($date, $refund->amount, true);
    //
    //     // Update leaderboard for refund
    //     $this->updateLeaderboardUseCase->handleRefund(
    //         $order->customer_id,
    //         $date,
    //         $refund->amount
    //     );
    // }

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

        // Note: RefundProcessed event may not exist yet, so we'll comment it out
        // $events->listen(
        //     RefundProcessed::class,
        //     [AnalyticsEventSubscriber::class, 'handleRefundProcessed']
        // );
    }
}
