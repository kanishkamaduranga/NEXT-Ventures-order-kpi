<?php

namespace Modules\Notifications\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Application\DTOs\NotificationData;
use Modules\Notifications\Domain\Models\Notification;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use Modules\Notifications\Infrastructure\Services\EmailNotificationChannel;
use Modules\Notifications\Infrastructure\Services\LogNotificationChannel;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public NotificationData $notificationData
    ) {
        // Set queue connection from config
        $this->onConnection(config('queue.default'));
    }

    public function handle(
        NotificationRepositoryInterface $notificationRepository,
        EmailNotificationChannel $emailChannel,
        LogNotificationChannel $logChannel
    ): void {
        // Create notification record
        $notification = $notificationRepository->create([
            'order_id' => $this->notificationData->orderId,
            'customer_id' => $this->notificationData->customerId,
            'status' => $this->notificationData->status,
            'total_amount' => $this->notificationData->totalAmount,
            'type' => $this->notificationData->type,
            'channel' => $this->notificationData->channel,
            'status_sent' => 'pending',
        ]);

        try {
            // Send notification based on channel
            $success = match ($this->notificationData->channel) {
                'email' => $emailChannel->send($this->notificationData),
                'log' => $logChannel->send($this->notificationData),
                default => false,
            };

            if ($success) {
                $notification->markAsSent();
            } else {
                $notification->markAsFailed('Notification channel returned false');
            }
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }
}

