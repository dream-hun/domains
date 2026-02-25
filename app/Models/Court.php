<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourtStatus;
use Carbon\CarbonInterface;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $name
 * @property-read string $country
 * @property-read string $city
 * @property-read ?float $latitude
 * @property-read ?float $longitude
 * @property-read CourtStatus $status
 * @property-read int $created_by
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read User $createdBy
 */
final class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<User, self> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'status' => CourtStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
