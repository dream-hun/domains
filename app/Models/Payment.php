<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function generatePendingIntentId(Order $order, int $attemptNumber): string
    {
        return sprintf('pending-%s-%d-%s', $order->id, $attemptNumber, Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    #[Scope]
    protected function latestForOrder(Builder $query, Order $order): Builder
    {
        return $query->where('order_id', $order->id)->orderByDesc('attempt_number')->orderByDesc('id');
    }

    #[Scope]
    protected function successful(Builder $query): Builder
    {
        return $query->where('status', 'succeeded');
    }

    #[Scope]
    protected function failed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'attempt_number' => 'integer',
            'failure_details' => 'array',
            'last_attempted_at' => 'datetime',
        ];
    }
}
