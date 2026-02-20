<?php

declare(strict_types=1);

use App\Actions\Hosting\PlanPrices\ActivateHostingPlanPriceAction;
use App\Models\HostingPlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('activate sets is_current to true on the price', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $action = new ActivateHostingPlanPriceAction();
    $action->handle($price);

    $price->refresh();
    expect($price->is_current)->toBeTrue();
});

test('activate deactivates previous current price for same plan currency and billing cycle', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $oldPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
        'effective_date' => now()->subDays(10),
    ]);

    $newPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $action = new ActivateHostingPlanPriceAction();
    $action->handle($newPrice);

    $oldPrice->refresh();
    $newPrice->refresh();

    expect($oldPrice->is_current)->toBeFalse()
        ->and($newPrice->is_current)->toBeTrue();
});

test('activate does not affect prices for different plan currency or billing cycle', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency1 = App\Models\Currency::factory()->create();
    $currency2 = App\Models\Currency::factory()->create();

    $price1 = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency1->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
    ]);

    $price2 = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency2->id,
        'billing_cycle' => 'monthly',
        'is_current' => false,
    ]);

    $action = new ActivateHostingPlanPriceAction();
    $action->handle($price2);

    $price1->refresh();
    expect($price1->is_current)->toBeTrue();
});

test('activate creates history entry with automatic activation reason', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $action = new ActivateHostingPlanPriceAction();
    $action->handle($price);

    $history = $price->hostingPlanPriceHistories()->latest()->first();

    expect($history)->not->toBeNull()
        ->and($history->reason)->toBe('Automatically activated on effective date')
        ->and($history->changed_by)->toBeNull();
});

test('activate does nothing if price is already current', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    $initialHistoryCount = $price->hostingPlanPriceHistories()->count();

    $action = new ActivateHostingPlanPriceAction();
    $action->handle($price);

    $price->refresh();
    expect($price->is_current)->toBeTrue()
        ->and($price->hostingPlanPriceHistories()->count())->toBe($initialHistoryCount);
});
