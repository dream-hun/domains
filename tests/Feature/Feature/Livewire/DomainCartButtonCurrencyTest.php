<?php

declare(strict_types=1);

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Livewire\DomainCartButton;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('mount does not query Tld when price and currency are provided', function (): void {
    $usd = Currency::query()->where('code', 'USD')->first();
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

    DB::enableQueryLog();
    $queryCountBefore = count(DB::getQueryLog());

    Livewire::test(DomainCartButton::class, [
        'domain' => 'example.com',
        'price' => '$12.00',
        'available' => true,
        'domainPrice' => null,
        'currency' => 'USD',
        'tldId' => $tld->id,
    ]);

    $queryCountAfter = count(DB::getQueryLog());
    $tldQueries = collect(DB::getQueryLog())
        ->slice($queryCountBefore)
        ->filter(fn (array $q): bool => preg_match('/from\s+[`"]?tld[`"]?\s+/i', $q['query'] ?? '') === 1);

    DB::disableQueryLog();

    expect($tldQueries->isEmpty())->toBeTrue('Expected no Tld table queries when price and currency are provided');
});

test('updateCurrency updates price when component is mounted with tld_id', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->first();
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($rwf)->not->toBeNull()->and($usd)->not->toBeNull();
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
        'register_price' => 5_000,
        'renew_price' => 5_000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld->id,
        'currency_id' => $usd->id,
        'register_price' => 5,
        'renew_price' => 5,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    session(['selected_currency' => 'RWF']);

    $component = Livewire::test(DomainCartButton::class, [
        'domain' => 'example.rw',
        'price' => '', // Empty so component loads Tld and can update price on currency change
        'available' => true,
        'domainPrice' => null,
        'currency' => 'RWF',
        'tldId' => $tld->id,
    ]);

    expect($component->get('currency'))->toBe('RWF');
    $initialPrice = $component->get('price');
    expect($initialPrice)->toContain('5'); // RWF formatted

    $component->dispatch('currency-changed', 'USD');

    expect($component->get('currency'))->toBe('USD')
        ->and($component->get('price'))->not->toBe($initialPrice)
        ->and($component->get('price'))->toContain('5'); // USD amount 5
});
