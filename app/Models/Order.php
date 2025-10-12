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

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'stripe_payment_intent_id',
        'stripe_session_id',
        'total_amount',
        'currency',
        'billing_email',
        'billing_name',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_postal_code',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'billing_address' => 'array',
    ];

    public static function generateOrderNumber(): string
    {
        $lastOrder = self::orderBy('id', 'desc')->first();

        if (! $lastOrder) {
            return 'ORD-'.date('Y').'-000001';
        }

        preg_match('/\d+$/', $lastOrder->order_number, $matches);
        $number = isset($matches[0]) ? (int) $matches[0] + 1 : 1;

        return 'ORD-'.date('Y').'-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

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
}
