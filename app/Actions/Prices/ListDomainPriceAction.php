<?php

declare(strict_types=1);

namespace App\Actions\Prices;

use App\Models\DomainPrice;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListDomainPriceAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return DomainPrice::query()->select(['uuid', 'tld', 'type', 'register_price', 'renewal_price', 'transfer_price', 'redemption_price'])->latest()->paginate($perPage);
    }
}
