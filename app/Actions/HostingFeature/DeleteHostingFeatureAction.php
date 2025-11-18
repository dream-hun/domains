<?php

declare(strict_types=1);

namespace App\Actions\HostingFeature;

use App\Models\HostingFeature;
use Illuminate\Support\Facades\Log;

final class DeleteHostingFeatureAction
{
    public function handle(HostingFeature $hostingFeature): void
    {
        $featureId = $hostingFeature->id;
        $featureName = $hostingFeature->name;

        $hostingFeature->delete();

        Log::info('Hosting feature deleted successfully', [
            'feature_id' => $featureId,
            'feature_name' => $featureName,
        ]);
    }
}
