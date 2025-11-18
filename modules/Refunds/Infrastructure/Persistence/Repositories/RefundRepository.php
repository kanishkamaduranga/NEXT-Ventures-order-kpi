<?php

namespace Modules\Refunds\Infrastructure\Persistence\Repositories;

use Modules\Refunds\Domain\Models\Refund;
use Modules\Refunds\Domain\Repositories\RefundRepositoryInterface;

class RefundRepository implements RefundRepositoryInterface
{
    public function findById(int $id): ?Refund
    {
        return Refund::find($id);
    }

    public function findByRefundId(string $refundId): ?Refund
    {
        return Refund::where('refund_id', $refundId)->first();
    }

    public function create(array $data): Refund
    {
        return Refund::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $refund = $this->findById($id);
        if (!$refund) {
            return false;
        }

        return $refund->update($data);
    }

    public function updateStatus(int $id, string $status, ?string $failureReason = null): bool
    {
        $refund = $this->findById($id);
        if (!$refund) {
            return false;
        }

        $data = ['status' => $status];
        if ($status === 'completed') {
            $data['processed_at'] = now();
        }
        if ($failureReason) {
            $data['failure_reason'] = $failureReason;
        }

        return $refund->update($data);
    }

    public function findByOrderId(int $orderId): array
    {
        return Refund::where('order_id', $orderId)->get()->all();
    }

    public function findByCustomerId(int $customerId): array
    {
        return Refund::where('customer_id', $customerId)->get()->all();
    }
}

