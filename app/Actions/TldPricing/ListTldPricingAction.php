<?php

declare(strict_types=1);

namespace App\Actions\TldPricing;

use App\Models\TldPricing;
use Illuminate\Support\Collection;

final class ListTldPricingAction
{
    public function handle(): Collection
    {
        return TldPricing::query()
            ->with(['tld', 'currency'])
            ->latest()
            ->get();
    }
}
