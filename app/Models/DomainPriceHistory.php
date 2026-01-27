<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DomainPriceHistory extends Model
{
    protected $guarded = [];

    public function domainPrice(): BelongsTo
    {
        return $this->belongsTo(DomainPrice::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'domain_price_id' => 'integer',
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
