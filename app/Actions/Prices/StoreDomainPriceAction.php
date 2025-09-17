<?php

declare(strict_types=1);

namespace App\Actions\Prices;

use App\Models\DomainPrice;
use Illuminate\Support\Str;

final class StoreDomainPriceAction
{
    public function handle(array $data): DomainPrice
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        if (array_key_exists('redemption_price', $data) && $data['redemption_price'] === '') {
            $data['redemption_price'] = null;
        }

        return DomainPrice::create($data);
    }
}
