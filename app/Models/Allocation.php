<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $game_id
 * @property-read int $player_id
 * @property-read float $total_amount
 * @property-read float $insurance_amount
 * @property-read float $savings_amount
 * @property-read float $pathway_amount
 * @property-read float $administration_amount
 * @property-read int $allocation_configuration_id
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read Game $game
 * @property-read User $player
 * @property-read AllocationConfiguration $allocationConfiguration
 */
final class Allocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<Game, self> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<User, self> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /** @return BelongsTo<AllocationConfiguration, self> */
    public function allocationConfiguration(): BelongsTo
    {
        return $this->belongsTo(AllocationConfiguration::class);
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'float',
            'insurance_amount' => 'float',
            'savings_amount' => 'float',
            'pathway_amount' => 'float',
            'administration_amount' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
