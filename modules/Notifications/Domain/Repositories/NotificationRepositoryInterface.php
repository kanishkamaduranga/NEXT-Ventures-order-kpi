<?php

namespace Modules\Notifications\Domain\Repositories;

use Modules\Notifications\Domain\Models\Notification;

interface NotificationRepositoryInterface
{
    public function create(array $data): Notification;
    public function findById(int $id): ?Notification;
    public function findByOrderId(int $orderId): array;
    public function findByCustomerId(int $customerId): array;
}

