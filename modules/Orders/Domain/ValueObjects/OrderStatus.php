<?php
namespace Modules\Orders\Domain\ValueObjects;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case RESERVING_STOCK = 'reserving_stock';
    case STOCK_RESERVED = 'stock_reserved';
    case STOCK_RESERVATION_FAILED = 'stock_reservation_failed';
    case PROCESSING_PAYMENT = 'processing_payment';
    case PAYMENT_SUCCEEDED = 'payment_succeeded';
    case PAYMENT_FAILED = 'payment_failed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::RESERVING_STOCK, self::CANCELLED]),
            self::RESERVING_STOCK => in_array($newStatus, [self::STOCK_RESERVED, self::STOCK_RESERVATION_FAILED]),
            self::STOCK_RESERVED => in_array($newStatus, [self::PROCESSING_PAYMENT, self::CANCELLED]),
            self::PROCESSING_PAYMENT => in_array($newStatus, [self::PAYMENT_SUCCEEDED, self::PAYMENT_FAILED]),
            self::PAYMENT_SUCCEEDED => in_array($newStatus, [self::COMPLETED]),
            self::PAYMENT_FAILED => in_array($newStatus, [self::CANCELLED]),
            self::STOCK_RESERVATION_FAILED => in_array($newStatus, [self::CANCELLED]),
            default => false,
        };
    }
}
