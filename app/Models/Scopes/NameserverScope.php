<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class NameserverScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('status', 'active') && $builder->where('type', 'default');
    }
}
