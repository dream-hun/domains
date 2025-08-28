<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Scopes\ContactScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ScopedBy(ContactScope::class)]
final class Contact extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'contact_type' => ContactType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);

    }

    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'domain_contacts');
    }

    /**
     * Get the contact's full name
     */
    public function getFullNameAttribute(): string
    {
        return mb_trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get the contact's full address
     */
    public function getFullAddressAttribute(): string
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

    /**
     * Scope to filter by contact type
     */
    public function scopeByContactType($query, string $contactType)
    {
        return $query->where('contact_type', $contactType);
    }

    /**
     * Scope to search contacts by name or email
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search): void {
            $q->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('organization', 'like', "%$search%");
        });
    }
}
