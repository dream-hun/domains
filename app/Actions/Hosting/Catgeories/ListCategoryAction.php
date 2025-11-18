<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Catgeories;

use App\Models\HostingCategory;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListCategoryAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return HostingCategory::query()
            ->select(['id', 'uuid', 'name', 'slug', 'icon', 'description', 'status', 'sort', 'created_at'])
            ->orderBy('sort', 'asc')
            ->latest()
            ->paginate($perPage);
    }
}
