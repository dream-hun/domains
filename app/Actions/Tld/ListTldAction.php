<?php

declare(strict_types=1);

namespace App\Actions\Tld;

use App\Models\Tld;
use Illuminate\Database\Eloquent\Collection;

final class ListTldAction
{
    public function handle(): Collection
    {
        return Tld::query()
            ->latest()
            ->get();
    }
}
