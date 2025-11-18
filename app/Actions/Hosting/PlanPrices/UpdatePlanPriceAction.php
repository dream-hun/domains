<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;

final class UpdatePlanPriceAction
{
    public function handle(string $uuid, array $data): void
    {
        $planPrice = HostingPlanPrice::query()->where('uuid', $uuid)->firstOrFail();
        $planPrice->update($data);
    }
}
