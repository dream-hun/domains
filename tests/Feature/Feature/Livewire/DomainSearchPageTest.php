<?php

declare(strict_types=1);

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Livewire\DomainSearchPage;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('domain search page component renders with selected currency from session', function (): void {
    session(['selected_currency' => 'RWF']);

    $component = Livewire::test(DomainSearchPage::class);

    $component->assertSet('selectedCurrency', 'RWF');
});

test('domain search page runs search on mount when domain query param is passed', function (): void {
    $usd = Currency::query()->where('code', 'USD')->first();
    expect($usd)->not->toBeNull();

    Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    $tld = Tld::query()->where('name', '.com')->first();
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

    session(['selected_currency' => 'USD']);

    $component = Livewire::test(DomainSearchPage::class, ['domain' => 'example.com']);

    $component->assertSet('searchPerformed', true)
        ->assertSet('searchedDomain', 'example.com');
    expect($component->get('details'))->not->toBeNull()
        ->and($component->get('details')['domain'])->toBe('example.com');
});

test('domain search page search method validates and sets error for empty domain', function (): void {
    $component = Livewire::test(DomainSearchPage::class)
        ->set('searchedDomain', '')
        ->call('search');

    $component->assertHasErrors('searchedDomain');
});

test('domain search page getDisplayPriceForItem returns price in selected currency', function (): void {
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
        'register_price' => 12,
        'renew_price' => 12,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $item = [
        'domain' => 'example.com',
        'tld_id' => $tld->id,
        'price' => '15,000 RWF',
        'currency' => 'RWF',
    ];

    $component = Livewire::test(DomainSearchPage::class)
        ->set('selectedCurrency', 'RWF');

    $priceRwf = $component->instance()->getDisplayPriceForItem($item);
    expect($priceRwf)->not->toBe('');

    $component->set('selectedCurrency', 'USD');
    $priceUsd = $component->instance()->getDisplayPriceForItem($item);
    expect($priceUsd)->not->toBe('')->and($priceUsd)->not->toBe($priceRwf);
});

test('domain search page handleCurrencyChanged updates selected currency and session', function (): void {
    session(['selected_currency' => 'USD']);

    $component = Livewire::test(DomainSearchPage::class)
        ->call('handleCurrencyChanged', 'RWF');

    $component->assertSet('selectedCurrency', 'RWF');

    expect(session('selected_currency'))->toBe('RWF');
});

test('get domains route renders domain search page with livewire component', function (): void {
    $response = $this->get(route('domains'));

    $response->assertStatus(200);
    $response->assertSeeLivewire('domain-search-page');
});

test('post domains search redirects to get domains with domain query param', function (): void {
    $response = $this->post(route('domains.search'), [
        'domain' => 'example.com',
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect();

    $redirect = $response->headers->get('Location');
    expect($redirect)->toContain('/domains');
    expect($redirect)->toContain('domain=example.com');
});
