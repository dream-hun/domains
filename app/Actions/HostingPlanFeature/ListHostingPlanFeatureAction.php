<?php

declare(strict_types=1);

namespace App\Actions\HostingPlanFeature;

use App\Models\HostingPlanFeature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ListHostingPlanFeatureAction
{
    public function handle(?int $hostingCategoryId = null, ?int $hostingPlanId = null): Collection
    {
        return HostingPlanFeature::query()
            ->with(['hostingPlan.category', 'hostingFeature'])
            ->when($hostingPlanId, fn (Builder $query) => $query->where('hosting_plan_id', $hostingPlanId))
            ->when(
                $hostingCategoryId,
                fn (Builder $query) => $query->whereHas(
                    'hostingPlan',
                    fn (Builder $planQuery) => $planQuery->where('category_id', $hostingCategoryId)
                )
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
