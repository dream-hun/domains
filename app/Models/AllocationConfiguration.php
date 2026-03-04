<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read float $insurance_percentage
 * @property-read float $savings_percentage
 * @property-read float $pathway_percentage
 * @property-read float $administration_percentage
 * @property-read ?int $updated_by
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read ?User $updatedBy
 */
final class AllocationConfiguration extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<User, self> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<Allocation, self> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    protected function casts(): array
    {
        return [
            'insurance_percentage' => 'float',
            'savings_percentage' => 'float',
            'pathway_percentage' => 'float',
            'administration_percentage' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
