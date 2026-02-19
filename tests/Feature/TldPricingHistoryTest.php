<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\DomainPriceHistory;
use App\Models\Tld;
use App\Models\TldPricing;
use App\Models\User;

it('creates a domain price history record when price fields change', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $tldPricing = TldPricing::factory()->create([
        'register_price' => 10,
        'renew_price' => 12,
        'transfer_price' => 5,
        'redemption_price' => null,
    ]);

    $tldPricing->update([
        'register_price' => 15,
        'renew_price' => 14,
    ]);

    expect(DomainPriceHistory::query()->count())->toBe(1);

    $history = DomainPriceHistory::query()->first();

    expect($history->tld_pricing_id)->toBe($tldPricing->id)
        ->and($history->register_price)->toBe(15)
        ->and($history->renewal_price)->toBe(14)
        ->and($history->changed_by)->toBe($user->id)
        ->and($history->old_values)->toBe([
            'register_price' => 10,
            'renew_price' => 12,
        ])
        ->and($history->changes)->toBe([
            'register_price' => 15,
            'renew_price' => 14,
        ]);
});

it('does not create history when non-price fields change', function (): void {
    $tldPricing = TldPricing::factory()->create();

    $tldPricing->update([
        'effective_date' => now()->addMonth(),
    ]);

    expect(DomainPriceHistory::query()->count())->toBe(0);
});

it('deactivates other current pricings when creating a new current pricing', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first();

    $existingPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    $newPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    $existingPricing->refresh();

    expect($existingPricing->is_current)->toBeFalse()
        ->and($newPricing->is_current)->toBeTrue();
});

it('deactivates other current pricings when updating is_current to true', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first();

    $firstPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    $secondPricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => false,
    ]);

    $secondPricing->update(['is_current' => true]);

    $firstPricing->refresh();

    expect($firstPricing->is_current)->toBeFalse()
        ->and($secondPricing->is_current)->toBeTrue();
});

it('does not deactivate pricings for different tld or currency', function (): void {
    $tld = Tld::factory()->create();
    $otherTld = Tld::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first();

    $otherTldPricing = TldPricing::factory()->create([
        'tld_id' => $otherTld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    $otherTldPricing->refresh();

    expect($otherTldPricing->is_current)->toBeTrue();
});

it('has domainPriceHistories relationship', function (): void {
    $tldPricing = TldPricing::factory()->create();

    expect($tldPricing->domainPriceHistories)->toBeEmpty();

    $tldPricing->update(['register_price' => 99]);

    $tldPricing->refresh();

    expect($tldPricing->domainPriceHistories)->toHaveCount(1);
});
