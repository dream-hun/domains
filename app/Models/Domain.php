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
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int $owner_id
 * @property int $years
 * @property Carbon|null $registered_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_renewed_at
 * @property float|null $custom_price
 * @property string|null $custom_price_currency
 * @property bool $is_custom_price
 * @property string|null $custom_price_notes
 * @property int|null $created_by_admin_id
 * @property int|null $subscription_id
 * @property DomainStatus|null $status
 * @property-read Collection<int, Contact> $contacts
 * @property-read User $owner
 * @property-read User|null $createdByAdmin
 * @property-read Subscription|null $subscription
 * @property-read Collection<int, Nameserver> $nameservers
 * @property-read Tld|null $domainPrice Tld accessed via tldPricing.tld
 * @property-read TldPricing|null $tldPricing
 * @property-read Collection<int, DomainRenewal> $renewals
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
     * @return BelongsTo<TldPricing, static>
     */
    public function tldPricing(): BelongsTo
    {
        return $this->belongsTo(TldPricing::class, 'tld_pricing_id');
    }

    /**
     * Tld for pricing/display (via tldPricing.tld).
     *
     * @return HasOneThrough<Tld, TldPricing, static>
     */
    public function domainPrice(): HasOneThrough
    {
        return $this->through('tldPricing')->has('tld');
    }

    /**
     * @return HasMany<DomainRenewal, static>
     */
    public function renewals(): HasMany
    {
        return $this->hasMany(DomainRenewal::class);
    }

    /**
     * @return BelongsTo<Subscription, static>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function getCustomPrice(): ?float
    {
        if (! $this->is_custom_price || $this->custom_price === null) {
            return null;
        }

        return (float) $this->custom_price;
    }

    public function isCustomPriced(): bool
    {
        return $this->is_custom_price && $this->custom_price !== null;
    }

    public function registeredAt(): ?string
    {
        return $this->registered_at ? $this->registered_at->format('d-m-Y') : null;

    }

    public function expiresAt(): ?string
    {
        return $this->expires_at ? $this->expires_at->format('d-m-Y') : null;
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
            'is_custom_price' => 'boolean',
            'custom_price' => 'decimal:2',
            'status' => DomainStatus::class,
        ];
    }
}
