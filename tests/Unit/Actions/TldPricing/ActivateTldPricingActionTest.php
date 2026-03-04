<?php

declare(strict_types=1);

use App\Actions\TldPricing\ActivateTldPricingAction;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('activate sets is_current to true on the pricing', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $pricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $action = new ActivateTldPricingAction();
    $action->handle($pricing);

    $pricing->refresh();
    expect($pricing->is_current)->toBeTrue();
});

test('activate deactivates previous current pricing for same tld and currency', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $oldPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
        'effective_date' => now()->subDays(10),
    ]);

    $newPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $action = new ActivateTldPricingAction();
    $action->handle($newPricing);

    $oldPricing->refresh();
    $newPricing->refresh();

    expect($oldPricing->is_current)->toBeFalse()
        ->and($newPricing->is_current)->toBeTrue();
});

test('activate does not affect pricings for different tld or currency', function (): void {
    $tld1 = Tld::factory()->create();
    $tld2 = Tld::factory()->create();
    $currency1 = Currency::factory()->create();
    $currency2 = Currency::factory()->create();

    $pricing1 = TldPricing::factory()->create([
        'tld_id' => $tld1->id,
        'currency_id' => $currency1->id,
        'is_current' => true,
    ]);

    $pricing2 = TldPricing::factory()->create([
        'tld_id' => $tld2->id,
        'currency_id' => $currency1->id,
        'is_current' => false,
    ]);

    $action = new ActivateTldPricingAction();
    $action->handle($pricing2);

    $pricing1->refresh();
    expect($pricing1->is_current)->toBeTrue();

    $pricing3 = TldPricing::factory()->create([
        'tld_id' => $tld1->id,
        'currency_id' => $currency2->id,
        'is_current' => false,
    ]);

    $action->handle($pricing3);

    $pricing1->refresh();
    expect($pricing1->is_current)->toBeTrue();
});

test('activate creates history entry with automatic activation reason', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $pricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $action = new ActivateTldPricingAction();
    $action->handle($pricing);

    $history = $pricing->domainPriceHistories()->latest()->first();

    expect($history)->not->toBeNull()
        ->and($history->reason)->toBe('Automatically activated on effective date')
        ->and($history->changed_by)->toBeNull();
});

test('activate does nothing if pricing is already current', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $pricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    $initialHistoryCount = $pricing->domainPriceHistories()->count();

    $action = new ActivateTldPricingAction();
    $action->handle($pricing);

    $pricing->refresh();
    expect($pricing->is_current)->toBeTrue()
        ->and($pricing->domainPriceHistories()->count())->toBe($initialHistoryCount);
});

test('activate creates history entry with previous pricing values when replacing current pricing', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $oldPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 100,
        'renew_price' => 120,
        'transfer_price' => 50,
        'redemption_price' => 200,
        'is_current' => true,
    ]);

    $newPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 150,
        'renew_price' => 140,
        'transfer_price' => 60,
        'redemption_price' => 250,
        'is_current' => false,
    ]);

    $action = new ActivateTldPricingAction();
    $action->handle($newPricing);

    $history = $newPricing->domainPriceHistories()->latest()->first();

    expect($history)->not->toBeNull()
        ->and($history->old_values)->toBe([
            'register_price' => 100,
            'renew_price' => 120,
            'transfer_price' => 50,
            'redemption_price' => 200,
        ])
        ->and($history->changes)->toBe([
            'register_price' => 150,
            'renew_price' => 140,
            'transfer_price' => 60,
            'redemption_price' => 250,
        ]);
});
