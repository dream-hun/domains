<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HostingPlanFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HostingPlanFeature extends Model
{
    /** @use HasFactory<HostingPlanFeatureFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'is_included' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function hostingPlan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class);
    }

    public function hostingFeature(): BelongsTo
    {
        return $this->belongsTo(HostingFeature::class);
    }

    public function getDisplayText(): string
    {
        if ($this->custom_text) {
            return $this->custom_text;
        }

        $feature = $this->hostingFeature;

        if (! $feature) {
            return '';
        }

        if ($this->is_unlimited) {
            return 'Unlimited '.$feature->name;
        }

        if ($this->feature_value === 'true') {
            return $feature->name;
        }

        if ($this->feature_value === 'false') {
            return $feature->name;
        }

        if ($this->feature_value === null || $this->feature_value === '') {
            return $feature->name;
        }

        $value = $this->feature_value;
        $unit = $feature->unit ? ' '.$feature->unit : '';

        return $value.$unit.' '.$feature->name;
    }
}
