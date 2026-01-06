<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListPlanPriceAction
{
    public function handle(int $perPage = 15, ?string $categoryUuid = null, ?string $planUuid = null, ?string $search = null): LengthAwarePaginator
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

        if ($search !== null) {
            $query->where(function ($q) use ($search): void {
                $q->where('billing_cycle', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('plan', function ($q) use ($search): void {
                        $q->where('name', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('category', function ($q) use ($search): void {
                                $q->where('name', 'like', sprintf('%%%s%%', $search));
                            });
                    });
            });
        }

        return $query->paginate($perPage);
    }
}
