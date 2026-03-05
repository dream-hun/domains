<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\HostingPlanPriceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a history record when price fields change', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $planPrice = HostingPlanPrice::factory()->create([
        'regular_price' => 10.00,
        'renewal_price' => 12.00,
    ]);

    $planPrice->update([
        'regular_price' => 15.00,
        'renewal_price' => 14.00,
    ]);

    expect(HostingPlanPriceHistory::query()->count())->toBe(1);

    $history = HostingPlanPriceHistory::query()->first();

    expect($history->hosting_plan_pricing_id)->toBe($planPrice->id)
        ->and($history->regular_price)->toBe(15.0)
        ->and($history->renewal_price)->toBe(14.0)
        ->and($history->changed_by)->toBe($user->id)
        ->and($history->old_values)->toEqual([
            'regular_price' => 10,
            'renewal_price' => 12,
        ])
        ->and($history->changes)->toEqual([
            'regular_price' => 15.0,
            'renewal_price' => 14.0,
        ]);
});

it('does not create history when non-price fields change', function (): void {
    $planPrice = HostingPlanPrice::factory()->create(['is_current' => false]);

    $planPrice->update(['effective_date' => now()->addMonth()->format('Y-m-d')]);

    expect(HostingPlanPriceHistory::query()->count())->toBe(0);
});

it('deactivates other current pricings when creating a new current pricing', function (): void {
    $plan = HostingPlan::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first();

    $existing = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
    ]);

    $newPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
    ]);

    $existing->refresh();

    expect($existing->is_current)->toBeFalse()
        ->and($newPrice->is_current)->toBeTrue();
});

it('deactivates other current pricings when updating is_current to true', function (): void {
    $plan = HostingPlan::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first();

    $first = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
    ]);

    $second = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => false,
    ]);

    $second->update(['is_current' => true]);

    $first->refresh();

    expect($first->is_current)->toBeFalse()
        ->and($second->is_current)->toBeTrue();
});

it('does not deactivate pricings for a different plan', function (): void {
    $plan = HostingPlan::factory()->create();
    $otherPlan = HostingPlan::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first();

    $otherPlanPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $otherPlan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
    ]);

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'billing_cycle' => 'monthly',
        'is_current' => true,
    ]);

    $otherPlanPrice->refresh();

    expect($otherPlanPrice->is_current)->toBeTrue();
});

it('has hostingPlanPriceHistories relationship', function (): void {
    $planPrice = HostingPlanPrice::factory()->create(['regular_price' => 20.00]);

    expect($planPrice->hostingPlanPriceHistories)->toBeEmpty();

    $planPrice->update(['regular_price' => 25.00]);

    $planPrice->refresh();

    expect($planPrice->hostingPlanPriceHistories)->toHaveCount(1);
});
