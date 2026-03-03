<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $player_id
 * @property-read string $format
 * @property-read int $wins
 * @property-read int $losses
 * @property-read int $total_games
 * @property-read int $recent_games
 * @property-read float $score
 * @property-read int $rank
 * @property-read int $ranking_configuration_id
 * @property-read CarbonInterface $calculated_at
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read User $player
 * @property-read RankingConfiguration $configuration
 */
final class PlayerRanking extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<User,self> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /** @return BelongsTo<RankingConfiguration,self> */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(RankingConfiguration::class, 'ranking_configuration_id');
    }

    protected function casts(): array
    {
        return [
            'wins' => 'integer',
            'losses' => 'integer',
            'total_games' => 'integer',
            'recent_games' => 'integer',
            'score' => 'float',
            'rank' => 'integer',
            'calculated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
