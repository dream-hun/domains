<?php

declare(strict_types=1);

use App\Actions\Domain\BuildDomainSearchResultAction;
use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('handle returns livewire result shape with all required keys', function (): void {
    $currency = Currency::factory()->create(['code' => 'GBP']);
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
        'register_price' => 2000,
        'renew_price' => 2000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);
    $tld->load(['tldPricings' => fn ($q) => $q->current()->with('currency')]);

    $action = new BuildDomainSearchResultAction;
    $result = $action->handle(
        $tld,
        'example.com',
        true,
        '',
        'GBP',
        false,
        true,
        true
    );

    expect($result)->toHaveKeys([
        'available',
        'reason',
        'register_price',
        'transfer_price',
        'renewal_price',
        'formatted_price',
        'display_currency_code',
        'in_cart',
        'is_primary',
        'is_international',
        'tld',
    ])
        ->and($result['tld'])->toBe('.com')
        ->and($result['available'])->toBeTrue()
        ->and($result['reason'])->toBe('')
        ->and($result['in_cart'])->toBeFalse()
        ->and($result['is_primary'])->toBeTrue()
        ->and($result['is_international'])->toBeTrue()
        ->and($result['register_price'])->toBe(20.0)
        ->and($result['display_currency_code'])->toBe('GBP')
        ->and($result['formatted_price'])->toContain('20');
});

test('handle includes tld in result', function (): void {
    $currency = Currency::factory()->create(['code' => 'EUR']);
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
        'register_price' => 5000,
        'renew_price' => 5000,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $action = new BuildDomainSearchResultAction;
    $result = $action->handle($tld, 'example.rw', true, '', 'EUR', false, true, false);

    expect($result['tld'])->toBe('.rw');
});

test('handle uses fallback currency when selected currency has no price', function (): void {
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

    $action = new BuildDomainSearchResultAction;
    $result = $action->handle(
        $tld,
        'example.com',
        true,
        '',
        'EUR',
        false,
        true,
        false
    );

    expect($result['register_price'])->toBe(12.0)
        ->and($result['display_currency_code'])->toBe('USD')
        ->and($result['formatted_price'])->toContain('12');
});
