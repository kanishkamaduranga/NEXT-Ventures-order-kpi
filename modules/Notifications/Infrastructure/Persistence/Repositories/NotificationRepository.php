<?php

namespace Modules\Notifications\Infrastructure\Persistence\Repositories;

use Modules\Notifications\Domain\Models\Notification;
use Modules\Notifications\Domain\Repositories\NotificationRepositoryInterface;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function findById(int $id): ?Notification
    {
        return Notification::find($id);
    }

    public function findByOrderId(int $orderId): array
    {
        return Notification::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function findByCustomerId(int $customerId): array
    {
        return Notification::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}

