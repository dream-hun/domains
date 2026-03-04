<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Models\AllocationConfiguration;
use App\Models\User;

final class UpdateAllocationConfiguration
{
    public function handle(
        float $insurancePercentage,
        float $savingsPercentage,
        float $pathwayPercentage,
        float $administrationPercentage,
        User $updatedBy,
    ): AllocationConfiguration {
        return AllocationConfiguration::query()->create([
            'insurance_percentage' => $insurancePercentage,
            'savings_percentage' => $savingsPercentage,
            'pathway_percentage' => $pathwayPercentage,
            'administration_percentage' => $administrationPercentage,
            'updated_by' => $updatedBy->id,
        ]);
    }
}
