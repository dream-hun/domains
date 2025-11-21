<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPlanPriceAction
{
    public function handle(int $perPage = 10, ?string $categoryUuid = null, ?string $planUuid = null): LengthAwarePaginator
    {
        $query = HostingPlanPrice::query()
            ->with(['plan.category'])
            ->orderByDesc('id');

        if ($categoryUuid !== null) {
            $query->whereHas('plan.category', function ($q) use ($categoryUuid): void {
                $q->where('uuid', $categoryUuid);
            });
        }

        if ($planUuid !== null) {
            $query->whereHas('plan', function ($q) use ($planUuid): void {
                $q->where('uuid', $planUuid);
            });
        }

        return $query->paginate($perPage);
    }
}
