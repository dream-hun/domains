<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Scopes\ContactScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property ContactType|null $contact_type
 * @property-read Collection<int, Domain> $domains
 * @property-read DomainContact $pivot
 * @property-read string $full_name
 * @property-read string $full_address
 */
#[ScopedBy(ContactScope::class)]
final class Contact extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'contact_type' => ContactType::class,
        'is_primary' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {

        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Domain, static, DomainContact, 'pivot'>
     */
    public function domains(): BelongsToMany
    {

        return $this->belongsToMany(Domain::class, 'domain_contacts', 'contact_id', 'domain_id')
            ->using(DomainContact::class)
            ->withPivot('type', 'user_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($contact): void {
            if (empty($contact->uuid)) {
                $contact->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Ensure country_code is always stored as uppercase.
     */
    protected function setCountryCodeAttribute(string $value): void
    {
        $this->attributes['country_code'] = mb_strtoupper($value);
    }

    /**
     * Get the contact's full name
     */
    protected function getFullNameAttribute(): string
    {
        return mb_trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get the contact's full address
     */
    protected function getFullAddressAttribute(): string
    {
        $address = $this->address_one;

        if ($this->address_two) {
            $address .= ', '.$this->address_two;
        }

        $address .= ', '.$this->city;
        $address .= ', '.$this->state_province;
        $address .= ' '.$this->postal_code;

        return $address.(', '.$this->country_code);
    }
}
