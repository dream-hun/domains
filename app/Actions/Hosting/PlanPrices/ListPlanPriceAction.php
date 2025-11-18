<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPlanPriceAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return HostingPlanPrice::query()
            ->with('plan')
            ->orderBy('id', 'desc')
            ->latest()
            ->paginate($perPage);
    }
}
