<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HostingFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingFeature extends Model
{
    /** @use HasFactory<HostingFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'icon',
        'category',
        'feature_category_id',
        'value_type',
        'unit',
        'sort_order',
        'is_highlighted',
    ];

    protected $casts = [
        'is_highlighted' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function featureCategory(): BelongsTo
    {
        return $this->belongsTo(FeatureCategory::class);
    }
}
