<?php

namespace Modules\Notifications\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Application\DTOs\NotificationData;

class LogNotificationChannel
{
    public function send(NotificationData $data): bool
    {
        try {
            $message = $this->formatMessage($data);

            Log::channel('single')->info('Order Notification', [
                'order_id' => $data->orderId,
                'customer_id' => $data->customerId,
                'status' => $data->status,
                'total_amount' => $data->totalAmount,
                'type' => $data->type,
                'message' => $message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to log notification', [
                'order_id' => $data->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function formatMessage(NotificationData $data): string
    {
        if ($data->type === 'order_completed') {
            return sprintf(
                "Order #%d completed successfully for customer #%d. Total: $%.2f",
                $data->orderId,
                $data->customerId,
                $data->totalAmount
            );
        } else {
            return sprintf(
                "Order #%d failed for customer #%d. Status: %s. Reason: %s",
                $data->orderId,
                $data->customerId,
                $data->status,
                $data->failureReason ?? 'Unknown'
            );
        }
    }
}

