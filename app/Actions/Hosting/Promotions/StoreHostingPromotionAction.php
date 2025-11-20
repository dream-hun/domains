<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Promotions;

use App\Models\HostingPromotion;
use Illuminate\Support\Str;

final class StoreHostingPromotionAction
{
    public function handle(array $data): HostingPromotion
    {
        $data['uuid'] ??= (string) Str::uuid();

        return HostingPromotion::query()->create($data);
    }
}
