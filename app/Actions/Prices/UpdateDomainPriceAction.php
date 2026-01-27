<?php

declare(strict_types=1);

namespace App\Actions\Prices;

use App\Models\DomainPrice;

final class UpdateDomainPriceAction
{
    public function handle(string $uuid, array $data): void
    {
        $domainPrice = DomainPrice::query()->where('uuid', $uuid)->firstOrFail();

        // Remove reason from data as it's not a model field, only used for history
        unset($data['reason']);

        $domainPrice->update($data);
    }
}
