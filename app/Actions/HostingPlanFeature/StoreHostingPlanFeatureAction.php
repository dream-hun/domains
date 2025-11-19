<?php

declare(strict_types=1);

namespace App\Actions\HostingPlanFeature;

use App\Models\HostingPlanFeature;
use Illuminate\Support\Str;

final class StoreHostingPlanFeatureAction
{
    public function handle(array $data): HostingPlanFeature
    {
        $data['uuid'] ??= (string) Str::uuid();

        return HostingPlanFeature::query()->create($data);
    }
}
