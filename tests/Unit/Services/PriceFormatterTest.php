<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Services\PriceFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('format uses Currency model symbol when Currency exists for code', function (): void {
    Currency::query()->create([
        'code' => 'TST',
        'name' => 'Test Currency',
        'symbol' => '¤',
        'is_base' => false,
        'is_active' => true,
    ]);

    $formatter = new PriceFormatter;
    $result = $formatter->format(20.0, 'TST');

    expect($result)->toContain('¤')->toContain('20');
});

test('getSymbol returns Currency model symbol when Currency exists for code', function (): void {
    Currency::query()->create([
        'code' => 'TST',
        'name' => 'Test Currency',
        'symbol' => '¤',
        'is_base' => false,
        'is_active' => true,
    ]);

    $formatter = new PriceFormatter;

    expect($formatter->getSymbol('TST'))->toBe('¤');
});

test('format uses config fallback when no Currency exists for code', function (): void {
    $formatter = new PriceFormatter;
    $result = $formatter->format(20.0, 'USD');

    expect($result)->toContain('$')->toContain('20');
});

test('getSymbol returns config fallback when no Currency exists for code', function (): void {
    $formatter = new PriceFormatter;

    expect($formatter->getSymbol('USD'))->toBe('$');
});

test('normalized currency code FRW uses RWF Currency when present', function (): void {
    $currency = Currency::query()->firstOrCreate(
        ['code' => 'RWF'],
        [
            'name' => 'Rwandan Franc',
            'symbol' => 'FRW',
            'is_base' => false,
            'is_active' => true,
        ]
    );

    $formatter = new PriceFormatter;

    expect($formatter->getSymbol('FRW'))->toBe($currency->symbol);
    expect($formatter->format(1000, 'FRW'))->toContain((string) $currency->symbol)->toContain('1,000');
});
