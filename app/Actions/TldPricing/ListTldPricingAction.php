<?php

declare(strict_types=1);

namespace App\Actions\TldPricing;

use App\Models\TldPricing;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListTldPricingAction
{
    public function handle(): LengthAwarePaginator
    {
        return TldPricing::query()
            ->with(['tld', 'currency'])
            ->latest()
            ->paginate(10);
    }
}
