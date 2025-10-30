<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

uses(\Tests\TestCase::class);

beforeEach(function () {
    Session::flush();
});

test('get currency symbol returns correct symbols', function () {
    $this->assertEquals('$', CurrencyHelper::getCurrencySymbol('USD'));
    $this->assertEquals('€', CurrencyHelper::getCurrencySymbol('EUR'));
    $this->assertEquals('FRW', CurrencyHelper::getCurrencySymbol('RWF'));
});

test('convert from usd with same currency', function () {
    $result = CurrencyHelper::convertFromUSD(100.0, 'USD');
    $this->assertEquals(100.0, $result);
});

test('format money', function () {
    // Mock the getRateAndSymbol method to be called inside Cache::remember
    Cache::shouldReceive('remember')
        ->zeroOrMoreTimes()
        ->andReturn(['rate' => 1.0, 'symbol' => '$']);

    $formatted = CurrencyHelper::formatMoney(100.50, 'USD');
    $this->assertEquals('$100.50', $formatted);
});

test('get user currency defaults to usd', function () {
    $currency = CurrencyHelper::getUserCurrency();
    $this->assertEquals('USD', $currency);
});
