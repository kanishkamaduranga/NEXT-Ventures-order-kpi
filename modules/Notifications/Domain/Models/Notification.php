<?php

namespace Modules\Notifications\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'order_id',
        'customer_id',
        'status',
        'total_amount',
        'type',
        'channel',
        'status_sent',
        'message',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function markAsSent(?string $message = null): void
    {
        $this->update([
            'status_sent' => 'sent',
            'sent_at' => now(),
            'message' => $message,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status_sent' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function isSent(): bool
    {
        return $this->status_sent === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status_sent === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status_sent === 'pending';
    }
}

