<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HostingPlanPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HostingPlanPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cycles = [
            'monthly' => [
                'regular_price' => 10000,
                'renewal_price' => 10000,
            ],
            'annually' => [
                'regular_price' => 100000,
                'renewal_price' => 100000,
            ],
        ];

        $planIds = range(1, 15);
        $prices = [];

        foreach ($planIds as $planId) {
            foreach ($cycles as $cycle => $amounts) {
                $prices[] = [
                    'uuid' => Str::uuid(),
                    'hosting_plan_id' => $planId,
                    'billing_cycle' => $cycle,
                    'regular_price' => $amounts['regular_price'],
                    'renewal_price' => $amounts['renewal_price'],
                    'status' => 'active',
                ];
            }
        }

        HostingPlanPrice::query()->insert($prices);
    }
}
