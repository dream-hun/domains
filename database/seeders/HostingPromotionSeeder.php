<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HostingPlan;
use App\Models\HostingPromotion;
use Illuminate\Database\Seeder;

class HostingPromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (HostingPlan::query()->count() === 0) {
            return;
        }

        HostingPlan::query()
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (HostingPlan $plan): void {
                HostingPromotion::factory()
                    ->count(2)
                    ->create(['hosting_plan_id' => $plan->id]);
            });
    }
}
