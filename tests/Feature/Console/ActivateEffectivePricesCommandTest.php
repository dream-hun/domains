<?php

declare(strict_types=1);

use App\Jobs\ActivateHostingPlanPriceJob;
use App\Models\HostingPlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;

uses(RefreshDatabase::class);

test('command finds and dispatches jobs for effective prices', function (): void {
    Bus::fake();

    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $effectivePrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => Date::today()->subDay(),
        'status' => 'active',
    ]);

    $this->artisan('hosting-prices:activate-effective')
        ->expectsOutput('Found 1 price(s) that need activation.')
        ->expectsOutputToContain('Dispatched activation job for price')
        ->assertSuccessful();

    Bus::assertDispatched(ActivateHostingPlanPriceJob::class, function ($job) use ($effectivePrice) {
        return $job->planPriceUuid === $effectivePrice->uuid;
    });
});

test('command does not dispatch jobs for prices with future effective dates', function (): void {
    Bus::fake();

    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => Date::today()->addDay(),
        'status' => 'active',
    ]);

    $this->artisan('hosting-prices:activate-effective')
        ->expectsOutput('No prices found that need activation.')
        ->assertSuccessful();

    Bus::assertNothingDispatched();
});

test('command does not dispatch jobs for prices that are already current', function (): void {
    Bus::fake();

    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => true,
        'effective_date' => Date::today()->subDay(),
        'status' => 'active',
    ]);

    $this->artisan('hosting-prices:activate-effective')
        ->expectsOutput('No prices found that need activation.')
        ->assertSuccessful();

    Bus::assertNothingDispatched();
});

test('command does not dispatch jobs for inactive prices', function (): void {
    Bus::fake();

    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => Date::today()->subDay(),
        'status' => 'inactive',
    ]);

    $this->artisan('hosting-prices:activate-effective')
        ->expectsOutput('No prices found that need activation.')
        ->assertSuccessful();

    Bus::assertNothingDispatched();
});

test('command handles multiple effective prices for same plan currency and billing cycle', function (): void {
    Bus::fake();

    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $olderPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => false,
        'effective_date' => Date::today()->subDays(2),
        'status' => 'active',
        'created_at' => now()->subDays(5),
    ]);

    $newerPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => false,
        'effective_date' => Date::today()->subDay(),
        'status' => 'active',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('hosting-prices:activate-effective')
        ->assertSuccessful();

    // Should dispatch job for newer price and skip older one
    Bus::assertDispatched(ActivateHostingPlanPriceJob::class, function ($job) use ($newerPrice) {
        return $job->planPriceUuid === $newerPrice->uuid;
    });

    Bus::assertNotDispatched(ActivateHostingPlanPriceJob::class, function ($job) use ($olderPrice) {
        return $job->planPriceUuid === $olderPrice->uuid;
    });
});

test('command processes multiple prices for different plan currency billing cycle combinations', function (): void {
    Bus::fake();

    $plan = App\Models\HostingPlan::factory()->create();
    $currency1 = App\Models\Currency::factory()->create();
    $currency2 = App\Models\Currency::factory()->create();

    $price1 = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency1->id,
        'billing_cycle' => 'monthly',
        'is_current' => false,
        'effective_date' => Date::today()->subDay(),
        'status' => 'active',
    ]);

    $price2 = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency2->id,
        'billing_cycle' => 'annually',
        'is_current' => false,
        'effective_date' => Date::today()->subDay(),
        'status' => 'active',
    ]);

    $this->artisan('hosting-prices:activate-effective')
        ->expectsOutput('Found 2 price(s) that need activation.')
        ->assertSuccessful();

    Bus::assertDispatched(ActivateHostingPlanPriceJob::class, 2);
    Bus::assertDispatched(ActivateHostingPlanPriceJob::class, function ($job) use ($price1) {
        return $job->planPriceUuid === $price1->uuid;
    });
    Bus::assertDispatched(ActivateHostingPlanPriceJob::class, function ($job) use ($price2) {
        return $job->planPriceUuid === $price2->uuid;
    });
});
