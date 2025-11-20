<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Promotions;

use App\Models\HostingPromotion;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListHostingPromotionAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return HostingPromotion::query()
            ->with(['plan.category'])
            ->orderByDesc('starts_at')
            ->paginate($perPage);
    }
}
