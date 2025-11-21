<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Promotions;

use App\Models\HostingPromotion;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListHostingPromotionAction
{
    public function handle(int $perPage = 10, ?int $categoryId = null, ?int $planId = null): LengthAwarePaginator
    {
        $query = HostingPromotion::query()
            ->with(['plan.category'])
            ->orderByDesc('starts_at');

        if ($categoryId !== null) {
            $query->whereHas('plan', function ($q) use ($categoryId): void {
                $q->where('category_id', $categoryId);
            });
        }

        if ($planId !== null) {
            $query->where('hosting_plan_id', $planId);
        }

        return $query->paginate($perPage);
    }
}
