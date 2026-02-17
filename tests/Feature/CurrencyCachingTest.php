<?php

declare(strict_types=1);

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

it('returns active currencies from cache', function (): void {
    Cache::forget('active_currencies');

    $currencies = Currency::getActiveCurrencies();

    expect($currencies)->toHaveCount(2)
        ->and($currencies->pluck('code')->toArray())->toContain('USD', 'RWF');

    expect(Cache::has('active_currencies'))->toBeTrue();
});

it('serves currencies from cache on subsequent calls', function (): void {
    Cache::forget('active_currencies');

    $firstCall = Currency::getActiveCurrencies();
    $secondCall = Currency::getActiveCurrencies();

    expect($firstCall->pluck('id')->toArray())->toBe($secondCall->pluck('id')->toArray());
});

it('does not include inactive currencies', function (): void {
    Cache::forget('active_currencies');

    Currency::factory()->inactive()->create(['code' => 'XYZ']);

    $currencies = Currency::getActiveCurrencies();

    expect($currencies->pluck('code'))->not->toContain('XYZ');
});
