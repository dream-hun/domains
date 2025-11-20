<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Promotions;

use App\Models\HostingPromotion;

final class UpdateHostingPromotionAction
{
    public function handle(string $uuid, array $data): void
    {
        $promotion = HostingPromotion::query()->where('uuid', $uuid)->firstOrFail();
        $promotion->update($data);
    }
}
