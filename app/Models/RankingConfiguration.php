<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read float $win_weight
 * @property-read float $loss_weight
 * @property-read float $game_count_weight
 * @property-read float $frequency_weight
 * @property-read ?int $updated_by
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read ?User $updatedBy
 */
final class RankingConfiguration extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<User,self> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<PlayerRanking,self> */
    public function rankings(): HasMany
    {
        return $this->hasMany(PlayerRanking::class);
    }

    protected function casts(): array
    {
        return [
            'win_weight' => 'float',
            'loss_weight' => 'float',
            'game_count_weight' => 'float',
            'frequency_weight' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
