<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $game_id
 * @property-read int $moderator_id
 * @property-read GameStatus $status
 * @property-read string $reason
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read Game $game
 * @property-read User $moderator
 */
final class GameModeration extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<Game, self> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<User, self> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
