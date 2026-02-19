<?php

declare(strict_types=1);

use App\Actions\Domain\BuildDomainSearchResultAction;
use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Livewire\DomainSearch;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->buildDomainSearchResult = new BuildDomainSearchResultAction;
});

test('handleCurrencyChanged recomputes results with new currency', function (): void {
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
        'currency_id' => $rwf->id,
        'register_price' => 15_000,
        'renew_price' => 15_000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
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

    Cache::forget('active_tlds');

    $domainName = 'example.com';
    $initialResult = $this->buildDomainSearchResult->handle(
        $tld->load(['tldPricings' => fn ($q) => $q->where('is_current', true)->with('currency')]),
        $domainName,
        true,
        '',
        'RWF',
        false,
        true,
        true
    );

    $component = Livewire::test(DomainSearch::class)
        ->set('results', [$domainName => $initialResult])
        ->set('currentCurrency', 'RWF');

    expect($component->get('results')[$domainName]['display_currency_code'])->toBe('RWF')
        ->and((float) $component->get('results')[$domainName]['register_price'])->toBe(15_000.0);

    $component->call('handleCurrencyChanged', 'USD');

    expect($component->get('currentCurrency'))->toBe('USD')
        ->and($component->get('results')[$domainName]['display_currency_code'])->toBe('USD')
        ->and((float) $component->get('results')[$domainName]['register_price'])->toBe(12.0);
});

test('handleCurrencyChanged does not cause N plus 1 queries when rebuilding multiple results', function (): void {
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($usd)->not->toBeNull();

    $tld1 = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld1->id,
        'currency_id' => $usd->id,
        'register_price' => 12,
        'renew_price' => 12,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    $tld2 = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.net',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => $tld2->id,
        'currency_id' => $usd->id,
        'register_price' => 15,
        'renew_price' => 15,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    Cache::forget('active_tlds');
    $tld1->load(['tldPricings' => fn ($q) => $q->where('is_current', true)->with('currency')]);
    $tld2->load(['tldPricings' => fn ($q) => $q->where('is_current', true)->with('currency')]);

    $results = [
        'example.com' => $this->buildDomainSearchResult->handle($tld1, 'example.com', true, '', 'USD', false, true, true),
        'example.net' => $this->buildDomainSearchResult->handle($tld2, 'example.net', true, '', 'USD', false, false, true),
    ];

    DB::enableQueryLog();
    $queryCountBefore = count(DB::getQueryLog());

    Livewire::test(DomainSearch::class)
        ->set('results', $results)
        ->set('currentCurrency', 'USD')
        ->call('handleCurrencyChanged', 'RWF');

    $queryCountAfter = count(DB::getQueryLog());
    $queriesRun = $queryCountAfter - $queryCountBefore;

    DB::disableQueryLog();

    // With eager loading, rebuilding results should not add a query per result (N+1).
    // Base queries: cache fill (TLDs + pricings + currencies), Currency lookups. Cap at 25.
    expect($queriesRun)->toBeLessThan(25);
});
