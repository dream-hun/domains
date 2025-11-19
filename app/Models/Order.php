<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User $user
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Collection<int, DomainRenewal> $domainRenewals
 * @property-read Collection<int, FailedDomainRegistration> $failedDomainRegistrations
 * @property-read Collection<int, Payment> $payments
 * @property string $payment_status
 * @property string $status
 */
final class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.mb_strtoupper(mb_substr(uniqid(), -10));
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<DomainRenewal, static>
     */
    public function domainRenewals(): HasMany
    {
        return $this->hasMany(DomainRenewal::class);
    }

    /**
     * @return HasMany<OrderItem, static>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<FailedDomainRegistration, static>
     */
    public function failedDomainRegistrations(): HasMany
    {
        return $this->hasMany(FailedDomainRegistration::class);
    }

    /**
     * @return HasMany<Payment, static>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPaymentAttempt(): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $this->payments()
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();

        return $payment;
    }

    public function latestSuccessfulPayment(): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $this->payments()
            ->successful()
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        return $payment;
    }

    public function latestFailedPayment(): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $this->payments()
            ->failed()
            ->orderByDesc('last_attempted_at')
            ->orderByDesc('id')
            ->first();

        return $payment;
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

    protected function casts(): array
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
}
