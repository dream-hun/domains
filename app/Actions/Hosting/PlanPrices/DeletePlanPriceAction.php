<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;

final class DeletePlanPriceAction
{
    public function handle(string $uuid): void
    {
        $planPrice = HostingPlanPrice::query()->where('uuid', $uuid)->firstOrFail();
        $planPrice->delete();
    }
}
