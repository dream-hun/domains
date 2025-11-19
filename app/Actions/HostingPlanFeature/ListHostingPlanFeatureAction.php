<?php

declare(strict_types=1);

namespace App\Actions\HostingPlanFeature;

use App\Models\HostingPlanFeature;
use Illuminate\Database\Eloquent\Collection;

final class ListHostingPlanFeatureAction
{
    public function handle(): Collection
    {
        return HostingPlanFeature::query()
            ->with(['hostingPlan', 'hostingFeature'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
