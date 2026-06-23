<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $domain_id
 * @property string $domain_name
 * @property string $domain_type
 * @property float $price
 * @property string $currency
 * @property float|null $exchange_rate
 * @property int $quantity
 * @property int $years
 * @property float $total_amount
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Order $order
 * @property-read Domain|null $domain
 */
#[Fillable([
    'order_id',
    'domain_id',
    'domain_name',
    'domain_type',
    'price',
    'currency',
    'exchange_rate',
    'quantity',
    'years',
    'total_amount',
    'metadata',
])]
final class OrderItem extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
