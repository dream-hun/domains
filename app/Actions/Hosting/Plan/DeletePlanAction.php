<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Plan;

use App\Models\HostingPlan;

final class DeletePlanAction
{
    public function handle(HostingPlan $hostingPlan): void
    {
        $hostingPlan->delete();
    }
}
