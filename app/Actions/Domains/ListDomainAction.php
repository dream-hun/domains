<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Collection;

final class ListDomainAction
{
    /**
     * @return Collection<int, Domain>
     */
    public function handle(): Collection
    {
        return Domain::query()
            ->with('domainPrice', 'owner')
            ->select(['id', 'uuid', 'name', 'registrar', 'provider', 'registered_at', 'auto_renew', 'expires_at', 'status', 'owner_id', 'domain_price_id'])
            ->latest()
            ->get();
    }
}
