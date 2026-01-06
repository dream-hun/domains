<?php

declare(strict_types=1);

namespace App\Actions\Prices;

use App\Models\DomainPrice;
use Illuminate\Database\Eloquent\Collection;

final class ListDomainPriceAction
{
    /**
     * @return Collection<int, DomainPrice>
     */
    public function handle(): Collection
    {
        return DomainPrice::query()
            ->select(['uuid', 'tld', 'type', 'register_price', 'renewal_price', 'transfer_price', 'redemption_price'])
            ->orderBy('id', 'desc')
            ->latest()
            ->get();
    }
}
