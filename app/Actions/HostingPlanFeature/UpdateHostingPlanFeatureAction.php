<?php

declare(strict_types=1);

namespace App\Actions\HostingPlanFeature;

use App\Models\HostingPlanFeature;

final class UpdateHostingPlanFeatureAction
{
    public function handle(HostingPlanFeature $hostingPlanFeature, array $data): HostingPlanFeature
    {
        $hostingPlanFeature->update($data);

        return $hostingPlanFeature->fresh();
    }
}
