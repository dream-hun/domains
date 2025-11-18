<?php

declare(strict_types=1);

namespace App\Actions\FeatureCategory;

use App\Models\FeatureCategory;
use Illuminate\Support\Str;

final class CreateFeatureCategoryAction
{
    public function handle(array $data): FeatureCategory
    {
        // Generate slug if not provided
        if (! isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        // Generate UUID if not provided
        if (! isset($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        // Ensure slug is unique
        $originalSlug = $data['slug'];
        $counter = 1;
        while (FeatureCategory::query()->where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        return FeatureCategory::query()->create($data);
    }
}
