<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainStatus;
use App\Models\Scopes\DomainScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $registered_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_renewed_at
 * @property-read Collection<int, Contact> $contacts
 * @property-read User $owner
 * @property-read Collection<int, Nameserver> $nameservers
 * @property-read DomainPrice|null $domainPrice
 * @property-read Collection<int, DomainRenewal> $renewals
 * @property DomainStatus|null $status
 */
#[ScopedBy([DomainScope::class])]
final class Domain extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function resolveRouteBinding($value, $field = null): Model
    {
        $field ??= $this->getRouteKeyName();

        return $this->newQueryWithoutScopes()
            ->where($field, $value)
            ->firstOrFail();
    }

    /**
     * @return BelongsToMany<Contact, static, DomainContact, 'pivot'>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'domain_contacts', 'domain_id', 'contact_id')
            ->using(DomainContact::class)
            ->withPivot('type', 'user_id');
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<Nameserver, static>
     */
    public function nameservers(): BelongsToMany
    {
        return $this->belongsToMany(Nameserver::class, 'domain_nameservers', 'domain_id', 'nameserver_id');
    }

    /**
     * @return BelongsTo<DomainPrice, static>
     */
    public function domainPrice(): BelongsTo
    {
        return $this->belongsTo(DomainPrice::class);
    }

    /**
     * @return HasMany<DomainRenewal, static>
     */
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
            'auto_renew' => 'boolean',
            'is_locked' => 'boolean',
            'status' => DomainStatus::class,
        ];
    }
}
