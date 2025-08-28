<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Domain extends Model
{
    protected $guarded = [];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'domain_contacts');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function nameservers(): BelongsToMany
    {
        return $this->belongsToMany(Nameserver::class, 'domain_nameservers');
    }

    public function domainPrice(): BelongsTo
    {
        return $this->belongsTo(DomainPrice::class);
    }

    public function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_renewed_at' => 'datetime',
        ];
    }
}
