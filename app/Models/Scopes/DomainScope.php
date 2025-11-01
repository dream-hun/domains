<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class DomainScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        
        // Skip scope if no authenticated user (e.g., running in queue/console)
        if (! $user) {
            return;
        }
        
        // Apply scope only for non-admin users
        if (! $user->isAdmin()) {
            $builder->where('owner_id', $user->id);
        }
    }
}
