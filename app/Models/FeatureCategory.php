<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\CategoryStatus;
use Database\Factories\FeatureCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureCategory extends Model
{
    /** @use HasFactory<FeatureCategoryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => CategoryStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hostingFeatures(): HasMany
    {
        return $this->hasMany(HostingFeature::class);
    }
}
