<?php
declare(strict_types=1);
namespace App\Actions\Prices;
use App\Models\DomainPrice;

final class UpdateDomainPriceAction
{
    public function handle(string $uuid, array $data): void
    {
        $domainPrice = DomainPrice::where('uuid', $uuid)->firstOrFail();
        $domainPrice->update($data);
    }
}
