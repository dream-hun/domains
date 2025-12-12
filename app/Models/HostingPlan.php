<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\Hosting\HostingPlanStatus;
use Database\Factories\HostingPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $uuid
 * @property int|null $category_id
 * @property int $sort_order
 * @property bool $is_popular
 * @property HostingPlanStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HostingCategory|null $category
 * @property-read Collection<int, HostingPlanPrice> $planPrices
 * @property-read Collection<int, HostingPlanFeature> $planFeatures
 * @property-read Collection<int, HostingPromotion> $promotions
 */
class HostingPlan extends Model
{
    /** @use HasFactory<HostingPlanFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sort_order' => 'integer',
        'is_popular' => 'boolean',
        'status' => HostingPlanStatus::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HostingCategory::class, 'category_id');
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(HostingPlanPrice::class, 'hosting_plan_id');
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(HostingPlanFeature::class, 'hosting_plan_id')->orderBy('sort_order');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(HostingPromotion::class, 'hosting_plan_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
