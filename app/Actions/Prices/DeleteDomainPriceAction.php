<?php

declare(strict_types=1);

namespace App\Actions\Prices;

use App\Models\DomainPrice;
use Illuminate\Validation\ValidationException;

final class DeleteDomainPriceAction
{
    public function handle(string $uuid): void
    {
        $domainPrice = DomainPrice::query()->where('uuid', $uuid)->firstOrFail();

        if ($domainPrice->domains()->exists()) {
            throw ValidationException::withMessages([
                'domain_price' => 'Cannot delete this price because it is associated with existing domains.',
            ]);
        }

        $domainPrice->delete();
    }
}
