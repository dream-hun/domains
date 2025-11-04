<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.strtoupper(substr(uniqid(), -10));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function domainRenewals(): HasMany
    {
        return $this->hasMany(DomainRenewal::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'items' => 'array',
            'billing_address' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    // Payment Status Methods
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->payment_status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->payment_status === 'refunded';
    }

    // Order Status Methods
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function requiresAttention(): bool
    {
        return $this->status === 'requires_attention';
    }

    public function isPartiallyCompleted(): bool
    {
        return $this->status === 'partially_completed';
    }

    /**
     * Get all order items - from relationship or fallback to JSON items
     */
    public function getAllOrderItems()
    {
        // First try to get from the relationship
        $orderItems = $this->orderItems;

        // If no order items in relationship, return empty collection
        // The items JSON field is just a snapshot and doesn't have the same structure
        return $orderItems;
    }
}
