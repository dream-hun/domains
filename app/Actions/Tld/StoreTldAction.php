<?php

declare(strict_types=1);

namespace App\Actions\Tld;

use App\Models\Tld;
use Illuminate\Support\Str;

final class StoreTldAction
{
    /**
     * @param  array{name: string, status: string, type: string}  $validated
     */
    public function handle(array $validated): Tld
    {
        return Tld::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => $validated['status'],
        ]);
    }
}
