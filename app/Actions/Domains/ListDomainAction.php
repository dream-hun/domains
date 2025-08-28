<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListDomainAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return Domain::query()->select(['uuid', 'name', 'registrar', 'provider', 'registered_at', 'auto_renew', 'expires_at', 'status'])->latest()->paginate($perPage);
    }
}
