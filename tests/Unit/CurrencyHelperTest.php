<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\TestCase;

final class CurrencyHelperTest extends TestCase
{
    public function test_get_currency_symbol_returns_correct_symbols(): void
    {
        $this->assertEquals('$', CurrencyHelper::getCurrencySymbol('USD'));
        $this->assertEquals('€', CurrencyHelper::getCurrencySymbol('EUR'));
        $this->assertEquals('FRw', CurrencyHelper::getCurrencySymbol('RWF'));
    }

    public function test_convert_from_usd_with_same_currency(): void
    {
        $result = CurrencyHelper::convertFromUSD(100.0, 'USD');
        $this->assertEquals(100.0, $result);
    }

    public function test_format_money(): void
    {
        // Mock the getRateAndSymbol method
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['rate' => 1.0, 'symbol' => '$']);

        $formatted = CurrencyHelper::formatMoney(100.50, 'USD');
        $this->assertEquals('$100.50', $formatted);
    }

    public function test_get_user_currency_defaults_to_usd(): void
    {
        $currency = CurrencyHelper::getUserCurrency();
        $this->assertEquals('USD', $currency);
    }
}
