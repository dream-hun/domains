<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\DomainScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([DomainScope::class])]
final class Domain extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();

        return $this->newQueryWithoutScopes()
            ->where($field, $value)
            ->firstOrFail();
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'domain_contacts', 'domain_id', 'contact_id')->withPivot('type', 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function nameservers(): BelongsToMany
    {
        return $this->belongsToMany(Nameserver::class, 'domain_nameservers', 'domain_id', 'nameserver_id');
    }

    public function domainPrice(): BelongsTo
    {
        return $this->belongsTo(DomainPrice::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(DomainRenewal::class);
    }

    public function registeredAt(): ?string
    {
        return $this->registered_at ? $this->registered_at->format('d-m-Y') : null;

    }

    public function expiresAt(): ?string
    {
        return $this->expires_at ? $this->expires_at->format('d-m-Y') : null;
    }

    public function lastRenewedAt(): ?string
    {
        return $this->last_renewed_at ? $this->last_renewed_at->format('d-m-Y') : null;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_renewed_at' => 'datetime',
            'is_locked' => 'boolean',
        ];
    }
}
