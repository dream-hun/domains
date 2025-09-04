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

/* #[ScopedBy([DomainScope::class])] */
final class Domain extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'domain_contacts', 'domain_id', 'contact_id')->withPivot('type', 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function nameservers(): HasMany
    {
        return $this->hasMany(Nameserver::class, 'domain_id');
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
            'is_locked' => 'boolean',
        ];
    }
}
