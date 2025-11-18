<?php

declare(strict_types=1);

namespace App\Actions\HostingFeature;

use App\Models\HostingFeature;
use Illuminate\Database\Eloquent\Collection;

final class ListHostingFeatureAction
{
    public function handle(): Collection
    {
        return HostingFeature::query()
            ->with('featureCategory')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
