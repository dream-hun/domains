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

function createTldPricing(Tld $tld, Currency $currency, int $registerPrice, int $renewPrice): TldPricing
{
    return TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => $registerPrice,
        'renew_price' => $renewPrice,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
}

test('home TLD component shows prices in session currency', function (): void {
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($usd)->not->toBeNull();

    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    createTldPricing($tld, $usd, 1200, 1200);

    session(['selected_currency' => 'USD']);

    $component = Livewire::test('home-tld-component');
    $html = $component->html();

    expect($html)->toContain('.com')
        ->and($html)->toContain('only')
        ->and($html)->toContain('12');
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
    createTldPricing($tld, $usd, 1200, 1200);
    createTldPricing($tld, $rwf, 15_000, 15_000);

    session(['selected_currency' => 'USD']);
    $component = Livewire::test('home-tld-component');
    $htmlUsd = $component->html();
    expect($htmlUsd)->toContain('12');

    session(['selected_currency' => 'RWF']);
    $component->dispatch('currency-changed', currency: 'RWF');
    $htmlRwf = $component->html();
    expect($htmlRwf)->toContain('15');
});
