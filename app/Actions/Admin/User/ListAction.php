<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAction
{
    /** @return LengthAwarePaginator<int, User> */
    public function handle(?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
