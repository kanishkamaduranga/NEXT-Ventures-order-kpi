<?php

namespace Modules\Orders\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasUuids, SoftDeletes;

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
}

