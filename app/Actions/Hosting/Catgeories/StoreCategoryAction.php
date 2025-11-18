<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Catgeories;

use App\Models\HostingCategory;
use Illuminate\Support\Str;

final readonly class StoreCategoryAction
{
    /**
     * Handle the creation of a hosting category
     *
     * @param  array<string, mixed>  $validatedData  The validated category data
     */
    public function handle(array $validatedData): bool
    {
        if (! isset($validatedData['uuid'])) {
            $validatedData['uuid'] = (string) Str::uuid();
        }

        if (! isset($validatedData['sort'])) {
            $maxSort = HostingCategory::query()->max('sort') ?? 0;
            $validatedData['sort'] = $maxSort + 1;
        }

        return HostingCategory::query()->create($validatedData)->save();
    }
}
