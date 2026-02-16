<?php

declare(strict_types=1);

namespace App\Actions\TldPricing;

use App\Models\TldPricing;

final class DeleteTldPricingAction
{
    public function handle(TldPricing $tldPricing): void
    {
        $tldPricing->delete();
    }
}
