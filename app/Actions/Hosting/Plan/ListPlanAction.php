<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Plan;

use App\Models\HostingPlan;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPlanAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return HostingPlan::query()
            ->select([
                'id',
                'uuid',
                'name',
                'slug',
                'description',
                'tagline',
                'is_popular',
                'status',
                'sort_order',
                'category_id',
                'created_at',
            ])
            ->with(['category:id,uuid,name,slug'])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
