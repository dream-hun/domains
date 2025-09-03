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
        return $this->belongsToMany(Domain::class, 'domain_contacts', 'contact_id', 'domain_id')->withPivot('type', 'user_id');
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
}
