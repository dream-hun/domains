<?php

declare(strict_types=1);

namespace App\Actions\FeatureCategory;

use App\Models\FeatureCategory;
use Illuminate\Database\Eloquent\Collection;

final class ListFeatureCategoryAction
{
    public function handle(): Collection
    {
        return FeatureCategory::query()
            ->withCount('hostingFeatures')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
