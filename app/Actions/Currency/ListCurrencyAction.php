<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

final class ListCurrencyAction
{
    public function handle(): Collection
    {
        return Currency::query()
            ->orderBy('is_base', 'desc')
            ->orderBy('code')
            ->get();
    }
}
