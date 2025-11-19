<?php

declare(strict_types=1);

namespace App\Models;

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
final class DomainContact extends Pivot
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'domain_contacts';

    protected $guarded = [];

    /**
     * @return BelongsTo<Contact, static>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Domain, static>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
