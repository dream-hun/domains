<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class HostingPlanPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = Currency::query()->where('is_active', true)->get();

        $cycles = [
            'monthly' => [
                'regular_price' => 1.00,
                'renewal_price' => 1.00,
            ],
            'annually' => [
                'regular_price' => 10.00,
                'renewal_price' => 10.00,
            ],
        ];

        $planIds = HostingPlan::query()->pluck('id')->all();
        $prices = [];

        foreach ($planIds as $planId) {
            foreach ($currencies as $currency) {
                foreach ($cycles as $cycle => $amounts) {
                    $prices[] = [
                        'uuid' => Str::uuid(),
                        'hosting_plan_id' => $planId,
                        'currency_id' => $currency->id,
                        'billing_cycle' => $cycle,
                        'regular_price' => $amounts['regular_price'],
                        'renewal_price' => $amounts['renewal_price'],
                        'status' => HostingPlanPriceStatus::Active->value,
                        'is_current' => true,
                        'effective_date' => now()->toDateString(),
                    ];
                }
            }
        }

        HostingPlanPrice::query()->insert($prices);
    }
}
