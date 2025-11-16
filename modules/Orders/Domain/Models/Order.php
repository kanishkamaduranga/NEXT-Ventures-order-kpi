<?php
// modules/Orders/Domain/Models/Order.php

namespace Modules\Orders\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Orders\Domain\Events\OrderProcessStarted;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'total_amount',
        'currency',
        'items',
        'customer_details',
        'reserved_at',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'items' => 'array',
        'customer_details' => 'array',
        'total_amount' => 'decimal:2',
        'reserved_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Start the order processing workflow
     */
    public function startProcessing(): void
    {
        event(new OrderProcessStarted($this));
    }

    /**
     * Check if order can be processed
     */
    public function canBeProcessed(): bool
    {
        return in_array($this->status, ['pending', 'reserved']);
    }

    /**
     * Check if order is completed successfully
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['cancelled', 'stock_reservation_failed', 'payment_failed']);
    }
}
