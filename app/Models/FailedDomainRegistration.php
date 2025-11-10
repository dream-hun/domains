<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FailedDomainRegistration extends Model
{
    use HasFactory;
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'domain_name',
        'failure_reason',
        'retry_count',
        'max_retries',
        'last_attempted_at',
        'next_retry_at',
        'resolved_at',
        'status',
        'contact_ids',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Check if this registration can be retried
     */
    public function canRetry(): bool
    {
        if ($this->status === 'resolved' || $this->status === 'abandoned') {
            return false;
        }

        return $this->retry_count < $this->max_retries;
    }

    /**
     * Increment the retry count and update last attempted timestamp
     */
    public function incrementRetryCount(): void
    {
        $this->update([
            'retry_count' => $this->retry_count + 1,
            'last_attempted_at' => now(),
            'status' => 'retrying',
        ]);
    }

    /**
     * Mark the registration as resolved
     */
    public function markResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark the registration as abandoned after all retries exhausted
     */
    public function markAbandoned(): void
    {
        $this->update([
            'status' => 'abandoned',
        ]);
    }

    protected function casts(): array
    {
        return [
            'contact_ids' => 'array',
            'last_attempted_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
