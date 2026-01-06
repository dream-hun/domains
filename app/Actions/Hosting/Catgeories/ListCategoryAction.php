<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Catgeories;

use App\Models\HostingCategory;
use Illuminate\Database\Eloquent\Collection;

final class ListCategoryAction
{
    /**
     * @return Collection<int, HostingCategory>
     */
    public function handle(): Collection
    {
        return HostingCategory::query()
            ->select(['id', 'uuid', 'name', 'slug', 'icon', 'description', 'status', 'sort', 'created_at'])
            ->orderBy('sort', 'asc')
            ->latest()
            ->get();
    }
}
