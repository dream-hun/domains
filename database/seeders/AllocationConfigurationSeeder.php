<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AllocationConfiguration;
use Illuminate\Database\Seeder;

final class AllocationConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        AllocationConfiguration::query()->create([
            'insurance_percentage' => 25.0,
            'savings_percentage' => 25.0,
            'pathway_percentage' => 25.0,
            'administration_percentage' => 25.0,
        ]);
    }
}
