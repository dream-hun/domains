<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;

final class UpdatePlanPriceAction
{
    public function handle(string $uuid, array $data): void
    {
        $planPrice = HostingPlanPrice::query()->with('currency')->where('uuid', $uuid)->firstOrFail();

        // Remove reason from data as it's not a model field, only used for history
        unset($data['reason']);

        $planPrice->update($data);
    }
}
