<?php

declare(strict_types=1);

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('tld accessor returns name', function (): void {
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);

    expect($tld->tld)->toBe('.com');
});

test('isLocalTld returns true for local type', function (): void {
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.rw',
        'type' => TldType::Local,
        'status' => TldStatus::Active,
    ]);

    expect($tld->isLocalTld())->toBeTrue();
});

test('isLocalTld returns false for international type', function (): void {
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);

    expect($tld->isLocalTld())->toBeFalse();
});

test('localTlds scope returns only local tlds', function (): void {
    Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.rw',
        'type' => TldType::Local,
        'status' => TldStatus::Active,
    ]);
    Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);

    $local = Tld::query()->localTlds()->get();

    expect($local)->toHaveCount(1)
        ->and($local->first()->name)->toBe('.rw');
});

test('internationalTlds scope returns only international tlds', function (): void {
    Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.rw',
        'type' => TldType::Local,
        'status' => TldStatus::Active,
    ]);
    Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);

    $international = Tld::query()->internationalTlds()->get();

    expect($international)->toHaveCount(1)
        ->and($international->first()->name)->toBe('.com');
});

test('getPriceForCurrency returns price from current tld pricing', function (): void {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 20,
        'renew_price' => 20,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $tld->load(['tldPricings' => fn ($q) => $q->current()->with('currency')]);

    expect($tld->getPriceForCurrency('EUR', 'register_price'))->toBe(20.0);
});

test('getPriceForCurrency returns major units for USD', function (): void {
    $currency = Currency::query()->where('code', 'USD')->firstOrFail();
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 180,
        'renew_price' => 180,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $tld->load(['tldPricings' => fn ($q) => $q->current()->with('currency')]);

    expect($tld->getPriceForCurrency('USD', 'register_price'))->toBe(180.0);
});

test('getPriceForCurrency returns major units for RWF', function (): void {
    $currency = Currency::query()->where('code', 'RWF')->firstOrFail();
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.rw',
        'type' => TldType::Local,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 10000,
        'renew_price' => 10000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $tld->load(['tldPricings' => fn ($q) => $q->current()->with('currency')]);

    expect($tld->getPriceForCurrency('RWF', 'register_price'))->toBe(10000.0);
});

test('getBaseCurrency returns currency code from first current pricing', function (): void {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 10,
        'renew_price' => 10,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    expect($tld->getBaseCurrency())->toBe('EUR');
});

test('getBaseCurrency returns default when no pricing', function (): void {
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);

    expect($tld->getBaseCurrency())->toBe('USD');
});

test('currentTldPricings returns only current pricings', function (): void {
    $currency = Currency::factory()->create();
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 10,
        'renew_price' => 10,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 20,
        'renew_price' => 20,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $current = $tld->currentTldPricings()->get();

    expect($current)->toHaveCount(1)
        ->and($current->first()->register_price)->toBe(10);
});

test('getDisplayPriceForCurrency returns preferred currency when it has price', function (): void {
    $eur = Currency::factory()->create(['code' => 'EUR']);
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $eur->id,
        'register_price' => 15,
        'renew_price' => 15,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $display = $tld->getDisplayPriceForCurrency('EUR', 'register_price');

    expect($display)->toBe(['amount' => 15.0, 'currency_code' => 'EUR']);
});

test('getDisplayPriceForCurrency falls back to app base when preferred has no price', function (): void {
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($usd)->not->toBeNull();
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $usd->id,
        'register_price' => 12,
        'renew_price' => 12,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    Cache::forget('base_currency');

    $display = $tld->getDisplayPriceForCurrency('EUR', 'register_price');

    expect($display['amount'])->toBe(12.0)
        ->and($display['currency_code'])->toBe('USD');
});

test('getDisplayPriceForCurrency falls back to TLD base when preferred and app base have no price', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->first();
    expect($rwf)->not->toBeNull();
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.rw',
        'type' => TldType::Local,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $rwf->id,
        'register_price' => 15000,
        'renew_price' => 15000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $display = $tld->getDisplayPriceForCurrency('EUR', 'register_price');

    expect($display['amount'])->toBe(15000.0)
        ->and($display['currency_code'])->toBe('RWF');
});

test('getDisplayPriceForCurrency normalizes FRW to RWF', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->first();
    expect($rwf)->not->toBeNull();
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.rw',
        'type' => TldType::Local,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $rwf->id,
        'register_price' => 10000,
        'renew_price' => 10000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $display = $tld->getDisplayPriceForCurrency('FRW', 'register_price');

    expect($display['amount'])->toBe(10000.0)
        ->and($display['currency_code'])->toBe('RWF');
});

test('getFormattedPriceWithFallback returns formatted string in resolved currency', function (): void {
    $eur = Currency::factory()->create(['code' => 'EUR']);
    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $eur->id,
        'register_price' => 20,
        'renew_price' => 20,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $formatted = $tld->getFormattedPriceWithFallback('register_price', 'EUR');

    expect($formatted)->toContain('20');
});
