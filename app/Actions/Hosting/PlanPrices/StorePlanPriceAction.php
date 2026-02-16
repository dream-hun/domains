<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Models\HostingPlanPrice;
use Illuminate\Support\Str;

final class StorePlanPriceAction
{
    public function handle(array $data): HostingPlanPrice
    {
        $data['uuid'] ??= (string) Str::uuid();
        $data['status'] ??= HostingPlanPriceStatus::Active;
        $data['is_current'] ??= true;
        $data['effective_date'] ??= now()->toDateString();

        return HostingPlanPrice::query()->create($data);
    }
}
