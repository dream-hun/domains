<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameStatus;
use Carbon\CarbonInterface;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $format
 * @property-read ?int $court_id
 * @property-read int $player_id
 * @property-read string $title
 * @property-read ?string $vimeo_uri
 * @property-read ?string $vimeo_status
 * @property-read CarbonInterface $played_at
 * @property-read GameStatus $status
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read ?Court $court
 * @property-read User $player
 */
final class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<Court, self> */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /** @return BelongsTo<User, self> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /** @return HasMany<GameModeration, self> */
    public function moderation(): HasMany
    {
        return $this->hasMany(GameModeration::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'played_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
