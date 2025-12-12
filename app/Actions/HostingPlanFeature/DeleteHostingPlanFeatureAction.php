<?php

declare(strict_types=1);

namespace App\Actions\HostingPlanFeature;

use App\Models\HostingPlan;
use App\Models\HostingFeature;
use App\Models\HostingPlanFeature;
use Illuminate\Support\Facades\Log;

final class DeleteHostingPlanFeatureAction
{
    public function handle(HostingPlanFeature $hostingPlanFeature): void
    {
        $hostingPlanFeature->loadMissing(['hostingPlan', 'hostingFeature']);

        $planFeatureId = $hostingPlanFeature->id;
        $hostingPlan = $hostingPlanFeature->hostingPlan;
        $hostingFeature = $hostingPlanFeature->hostingFeature;
        $planName = $hostingPlan instanceof HostingPlan ? $hostingPlan->name : null;
        $featureName = $hostingFeature instanceof HostingFeature ? $hostingFeature->name : null;

        $hostingPlanFeature->delete();

        Log::info('Hosting plan feature deleted successfully', [
            'plan_feature_id' => $planFeatureId,
            'plan_name' => $planName,
            'feature_name' => $featureName,
        ]);
    }
}
