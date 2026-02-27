<?php

declare(strict_types=1);

namespace App\Actions\Admin\Court;

use App\Models\Court;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAction
{
    /** @return LengthAwarePaginator<int, Court> */
    public function handle(?string $search = null): LengthAwarePaginator
    {
        return Court::query()
            ->with('createdBy')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('country', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('city', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
