<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DomainPriceHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<TldPricing, static>
     */
    public function tldPricing(): BelongsTo
    {
        return $this->belongsTo(TldPricing::class, 'tld_pricing_id');
    }

    /**
     * Tld for backward compatibility (via tldPricing.tld).
     */
    public function domainPrice(): ?Tld
    {
        return $this->tldPricing?->tld;
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'tld_pricing_id' => 'integer',
            'changed_by' => 'integer',
            'reason' => 'string',
            'register_price' => 'integer',
            'renewal_price' => 'integer',
            'transfer_price' => 'integer',
            'redemption_price' => 'integer',
            'changes' => 'array',
            'old_values' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
