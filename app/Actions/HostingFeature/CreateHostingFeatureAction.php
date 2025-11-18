<?php

declare(strict_types=1);

namespace App\Actions\HostingFeature;

use App\Models\HostingFeature;
use Illuminate\Support\Str;

final class CreateHostingFeatureAction
{
    public function handle(array $data): HostingFeature
    {
        // Generate UUID if not provided
        if (! isset($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        // Generate slug if not provided
        if (! isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        // Ensure slug is unique
        $originalSlug = $data['slug'];
        $counter = 1;
        while (HostingFeature::query()->where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        // Set default values
        $data['sort_order'] ??= 0;
        $data['is_highlighted'] ??= false;
        $data['category'] ??= 'general';
        $data['value_type'] ??= 'text';

        return HostingFeature::query()->create($data);
    }
}
