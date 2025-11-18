<?php

declare(strict_types=1);

namespace App\Actions\FeatureCategory;

use App\Models\FeatureCategory;
use Illuminate\Support\Str;

final class UpdateFeatureCategoryAction
{
    public function handle(FeatureCategory $featureCategory, array $data): FeatureCategory
    {
        // Update slug if name changed or slug is provided
        if (isset($data['name']) && $data['name'] !== $featureCategory->name) {
            if (! isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            } else {
                $data['slug'] = Str::slug($data['slug']);
            }

            // Ensure slug is unique (excluding current category)
            $originalSlug = $data['slug'];
            $counter = 1;
            while (FeatureCategory::query()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $featureCategory->id)
                ->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        } elseif (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);

            // Ensure slug is unique (excluding current category)
            $originalSlug = $data['slug'];
            $counter = 1;
            while (FeatureCategory::query()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $featureCategory->id)
                ->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        $featureCategory->update($data);

        return $featureCategory->fresh();
    }
}
