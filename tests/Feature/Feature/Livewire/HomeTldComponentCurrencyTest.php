<?php

declare(strict_types=1);

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('home TLD component shows prices in session currency', function (): void {
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
        'register_price' => 1200,
        'renew_price' => 1200,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    session(['selected_currency' => 'USD']);

    $component = Livewire::test('home-tld-component');

    $html = $component->html();
    expect($html)->toContain('.com')
        ->and($html)->toContain('only')
        ->and($html)->toContain('12'); // USD $12.00
});

test('home TLD component updates prices when currency changes', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->first();
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($rwf)->not->toBeNull()->and($usd)->not->toBeNull();

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
        'register_price' => 1200,
        'renew_price' => 1200,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $rwf->id,
        'register_price' => 15_000,
        'renew_price' => 15_000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    session(['selected_currency' => 'USD']);

    $component = Livewire::test('home-tld-component');
    $htmlUsd = $component->html();
    expect($htmlUsd)->toContain('12'); // USD $12.00

    session(['selected_currency' => 'RWF']);
    $component->dispatch('currency-changed', currency: 'RWF');

    $htmlRwf = $component->html();
    expect($htmlRwf)->toContain('15'); // RWF 15000 or formatted
});
