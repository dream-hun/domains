<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    /** @return BelongsTo<User,self> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /** @return BelongsTo<Country,self> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the attributes that should be cast.
     * @return array<string,string>
     */
    public function casts(): array
    {
        return [
            'country_id' => 'integer',
            'player_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
