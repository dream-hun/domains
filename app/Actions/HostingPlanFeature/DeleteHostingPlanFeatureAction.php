<?php

declare(strict_types=1);

namespace App\Actions\HostingPlanFeature;

use App\Models\HostingPlanFeature;
use Illuminate\Support\Facades\Log;

final class DeleteHostingPlanFeatureAction
{
    public function handle(HostingPlanFeature $hostingPlanFeature): void
    {
        $planFeatureId = $hostingPlanFeature->id;
        $planName = $hostingPlanFeature->hostingPlan?->name ?? 'Unknown';
        $featureName = $hostingPlanFeature->hostingFeature?->name ?? 'Unknown';

        $hostingPlanFeature->delete();

        Log::info('Hosting plan feature deleted successfully', [
            'plan_feature_id' => $planFeatureId,
            'plan_name' => $planName,
            'feature_name' => $featureName,
        ]);
    }
}
