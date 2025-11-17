<?php

namespace Modules\Notifications\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Application\DTOs\NotificationData;

class EmailNotificationChannel
{
    public function send(NotificationData $data): bool
    {
        try {
            // For now, we'll log the email since we don't have a mail template
            // In production, you would send an actual email here
            $subject = $this->getSubject($data);
            $body = $this->getBody($data);

            Log::info('Email notification would be sent', [
                'to' => "customer_{$data->customerId}@example.com", // In production, get from customer details
                'subject' => $subject,
                'body' => $body,
            ]);

            // TODO: Uncomment when mail is configured
            // Mail::raw($body, function ($message) use ($data, $subject) {
            //     $message->to("customer_{$data->customerId}@example.com")
            //             ->subject($subject);
            // });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'order_id' => $data->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function getSubject(NotificationData $data): string
    {
        return match ($data->type) {
            'order_completed' => "Order #{$data->orderId} Confirmed",
            'order_failed' => "Order #{$data->orderId} Failed",
            default => "Order #{$data->orderId} Update",
        };
    }

    private function getBody(NotificationData $data): string
    {
        if ($data->type === 'order_completed') {
            return "Your order #{$data->orderId} has been successfully processed.\n\n"
                . "Status: {$data->status}\n"
                . "Total Amount: $" . number_format($data->totalAmount, 2) . "\n\n"
                . "Thank you for your purchase!";
        } else {
            return "Your order #{$data->orderId} could not be processed.\n\n"
                . "Status: {$data->status}\n"
                . "Reason: " . ($data->failureReason ?? 'Unknown error') . "\n\n"
                . "Please contact support if you need assistance.";
        }
    }
}

