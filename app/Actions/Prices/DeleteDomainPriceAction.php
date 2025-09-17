<?php

declare(strict_types=1);
namespace App\Actions\Prices;

use App\Models\DomainPrice;

final class DeleteDomainPriceAction
{
    public function handle(string $uuid): void
    {
        $domainPrice = DomainPrice::where('uuid', $uuid)->firstOrFail();
        $domainPrice->delete();
    }
}
