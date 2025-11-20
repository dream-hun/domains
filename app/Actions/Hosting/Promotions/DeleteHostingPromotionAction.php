<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Promotions;

use App\Models\HostingPromotion;

final class DeleteHostingPromotionAction
{
    public function handle(string $uuid): void
    {
        HostingPromotion::query()->where('uuid', $uuid)->delete();
    }
}
