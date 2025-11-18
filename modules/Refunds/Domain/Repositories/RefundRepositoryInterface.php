<?php

namespace Modules\Refunds\Domain\Repositories;

use Modules\Refunds\Domain\Models\Refund;

interface RefundRepositoryInterface
{
    public function findById(int $id): ?Refund;

    public function findByRefundId(string $refundId): ?Refund;

    public function create(array $data): Refund;

    public function update(int $id, array $data): bool;

    public function updateStatus(int $id, string $status, ?string $failureReason = null): bool;

    public function findByOrderId(int $orderId): array;

    public function findByCustomerId(int $customerId): array;
}

