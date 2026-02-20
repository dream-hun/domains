<?php

declare(strict_types=1);

use App\Enums\Hosting\BillingCycle;
use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Enums\Hosting\HostingPlanStatus;
use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('home hosting component shows prices in session currency', function (): void {
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($usd)->not->toBeNull();

    $plan = HostingPlan::factory()->create([
        'status' => HostingPlanStatus::Active,
        'name' => 'Starter',
    ]);
    HostingPlanPrice::query()->create([
        'uuid' => (string) Str::uuid(),
        'hosting_plan_id' => $plan->id,
        'currency_id' => $usd->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'regular_price' => 10.00,
        'renewal_price' => 10.00,
        'status' => HostingPlanPriceStatus::Active->value,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $plan->load(['planPrices.currency']);

    session(['selected_currency' => 'USD']);

    $component = Livewire::test('home-hosting-component', [
        'hostingPlans' => collect([$plan]),
        'selectedCurrency' => 'USD',
    ]);
    $html = $component->html();

    expect($html)->toContain('Starter')
        ->and($html)->toContain('Starting at')
        ->and($html)->toContain('10');
});

test('home hosting component updates prices when currency changes', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->first();
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($rwf)->not->toBeNull()->and($usd)->not->toBeNull();

    $plan = HostingPlan::factory()->create([
        'status' => HostingPlanStatus::Active,
        'name' => 'Pro',
    ]);
    HostingPlanPrice::query()->create([
        'uuid' => (string) Str::uuid(),
        'hosting_plan_id' => $plan->id,
        'currency_id' => $usd->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'regular_price' => 12.00,
        'renewal_price' => 12.00,
        'status' => HostingPlanPriceStatus::Active->value,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    HostingPlanPrice::query()->create([
        'uuid' => (string) Str::uuid(),
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'regular_price' => 15_000,
        'renewal_price' => 15_000,
        'status' => HostingPlanPriceStatus::Active->value,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $plan->load(['planPrices.currency']);

    session(['selected_currency' => 'USD']);
    $component = Livewire::test('home-hosting-component', [
        'hostingPlans' => collect([$plan]),
        'selectedCurrency' => 'USD',
    ]);
    $htmlUsd = $component->html();
    expect($htmlUsd)->toContain('12');

    $component->dispatch('currency-changed', currency: 'RWF');
    $htmlRwf = $component->html();
    expect($htmlRwf)->toContain('15');
});
