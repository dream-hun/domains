<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use Database\Factories\HostingFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $uuid
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $category
 * @property int|null $feature_category_id
 * @property string|null $value_type
 * @property string|null $unit
 * @property int $sort_order
 * @property bool $is_highlighted
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FeatureCategory|null $featureCategory
 */
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
