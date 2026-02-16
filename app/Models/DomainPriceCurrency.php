<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DomainPriceCurrency extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function domainPrice(): BelongsTo
    {
        return $this->belongsTo(Tld::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    protected function casts(): array
    {
        return [
            'registration_price' => 'integer',
            'renewal_price' => 'integer',
            'transfer_price' => 'integer',
            'redemption_price' => 'decimal:2',
            'is_current' => 'boolean',
            'effective_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
