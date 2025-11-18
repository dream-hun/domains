<?php

declare(strict_types=1);

namespace App\Actions\HostingFeature;

use App\Models\HostingFeature;
use Illuminate\Support\Str;

final class UpdateHostingFeatureAction
{
    public function handle(HostingFeature $hostingFeature, array $data): HostingFeature
    {
        // Update slug if name changed or slug is provided
        if (isset($data['name']) && $data['name'] !== $hostingFeature->name) {
            if (! isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            } else {
                $data['slug'] = Str::slug($data['slug']);
            }

            // Ensure slug is unique (excluding current feature)
            $originalSlug = $data['slug'];
            $counter = 1;
            while (HostingFeature::query()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $hostingFeature->id)
                ->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        } elseif (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);

            // Ensure slug is unique (excluding current feature)
            $originalSlug = $data['slug'];
            $counter = 1;
            while (HostingFeature::query()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $hostingFeature->id)
                ->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        $hostingFeature->update($data);

        return $hostingFeature->fresh();
    }
}
