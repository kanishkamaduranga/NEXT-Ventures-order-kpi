<?php

namespace Modules\Notifications\Infrastructure\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Notifications\Application\DTOs\NotificationData;
use Modules\Notifications\Application\Jobs\SendOrderNotificationJob;
use Modules\Orders\Domain\Events\OrderCompleted;
use Modules\Orders\Domain\Events\OrderFailed;

class NotificationEventSubscriber
{
    public function handleOrderCompleted(OrderCompleted $event): void
    {
        $order = $event->order;

        // Send email notification
        SendOrderNotificationJob::dispatch(
            new NotificationData(
                orderId: (int) $order->id,
                customerId: (int) $order->customer_id,
                status: $order->status,
                totalAmount: (float) $order->total_amount,
                type: 'order_completed',
                channel: 'email'
            )
        );

        // Send log notification
        SendOrderNotificationJob::dispatch(
            new NotificationData(
                orderId: (int) $order->id,
                customerId: (int) $order->customer_id,
                status: $order->status,
                totalAmount: (float) $order->total_amount,
                type: 'order_completed',
                channel: 'log'
            )
        );
    }

    public function handleOrderFailed(OrderFailed $event): void
    {
        $order = $event->order;

        // Send email notification
        SendOrderNotificationJob::dispatch(
            new NotificationData(
                orderId: (int) $order->id,
                customerId: (int) $order->customer_id,
                status: $order->status,
                totalAmount: (float) $order->total_amount,
                type: 'order_failed',
                channel: 'email',
                failureReason: $event->reason
            )
        );

        // Send log notification
        SendOrderNotificationJob::dispatch(
            new NotificationData(
                orderId: (int) $order->id,
                customerId: (int) $order->customer_id,
                status: $order->status,
                totalAmount: (float) $order->total_amount,
                type: 'order_failed',
                channel: 'log',
                failureReason: $event->reason
            )
        );
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            OrderCompleted::class,
            [NotificationEventSubscriber::class, 'handleOrderCompleted']
        );

        $events->listen(
            OrderFailed::class,
            [NotificationEventSubscriber::class, 'handleOrderFailed']
        );
    }
}

