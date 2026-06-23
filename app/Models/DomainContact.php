<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $domain_id
 * @property int $contact_id
 * @property string $type
 * @property int|null $user_id
 * @property-read Contact $contact
 * @property-read Domain $domain
 * @property-read User|null $user
 */
#[Table(name: 'domain_contacts')]
#[WithoutTimestamps]
final class DomainContact extends Pivot
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Contact, static>
     */
    public function contact(): BelongsTo
    {
        // @phpstan-ignore-next-line
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Domain, static>
     */
    public function domain(): BelongsTo
    {
        // @phpstan-ignore-next-line
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        // @phpstan-ignore-next-line
        return $this->belongsTo(User::class);
    }
}
