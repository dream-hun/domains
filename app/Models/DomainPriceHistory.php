<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainPriceHistory extends Model
{
    protected $guarded = [];

    public function casts(): array
    {
        return [
            'domain_price_id' => 'integer',
            'changed_by' => 'integer',
            'reason' => 'string',
            'register_price' => 'integer',
            'renewal_price' => 'integer',
            'transfer_price' => 'integer',
            'redemption_price' => 'integer',
        ];
    }

    public function domainPrices(): HasMany
    {
        return $this->hasMany(DomainPrice::class);
    }
}
