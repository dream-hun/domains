<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

final class TldPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prices = [
            [
                'tld' => '.com',
                'type' => 'international',
                'register_price' => 18000,
                'renew_price' => 20000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.org',
                'type' => 'international',
                'register_price' => 18000,
                'renew_price' => 20000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.net',
                'type' => 'international',
                'register_price' => 14000,
                'renew_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.cloud',
                'type' => 'international',
                'register_price' => 15000,
                'renew_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.io',
                'type' => 'international',
                'register_price' => 60000,
                'renew_price' => 70000,
                'transfer_price' => 30000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.app',
                'type' => 'international',
                'register_price' => 20000,
                'renew_price' => 22000,
                'transfer_price' => 12000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.dev',
                'type' => 'international',
                'register_price' => 20000,
                'renew_price' => 22000,
                'transfer_price' => 12000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.tech',
                'type' => 'international',
                'register_price' => 15000,
                'renew_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.xyz',
                'type' => 'international',
                'register_price' => 5000,
                'renew_price' => 8000,
                'transfer_price' => 5000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.me',
                'type' => 'international',
                'register_price' => 15000,
                'renew_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.rw',
                'type' => 'local',
                'register_price' => 10000,
                'renew_price' => 12000,
                'transfer_price' => 8000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.co.rw',
                'type' => 'local',
                'register_price' => 15000,
                'renew_price' => 17000,
                'transfer_price' => 12000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.org.rw',
                'type' => 'local',
                'register_price' => 12000,
                'renew_price' => 14000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.net.rw',
                'type' => 'local',
                'register_price' => 12000,
                'renew_price' => 14000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
            [
                'tld' => '.ac.rw',
                'type' => 'local',
                'register_price' => 20000,
                'renew_price' => 22000,
                'transfer_price' => 15000,
                'redemption_price' => 0,
                'status' => 'active',
            ],
        ];

        $usdCurrency = $this->getCurrency('USD');
        $rwfCurrency = $this->getCurrency('RWF');

        foreach ($prices as $priceData) {
            $tld = $this->createOrGetTld($priceData);
            $currencyId = $this->getCurrencyIdForType($priceData['type'], $usdCurrency, $rwfCurrency);

            TldPricing::query()->updateOrCreate(
                [
                    'tld_id' => $tld->id,
                    'currency_id' => $currencyId,
                    'is_current' => true,
                ],
                [
                    'uuid' => Str::uuid()->toString(),
                    'tld_id' => $tld->id,
                    'currency_id' => $currencyId,
                    'register_price' => $priceData['register_price'],
                    'renew_price' => $priceData['renew_price'],
                    'transfer_price' => $priceData['transfer_price'],
                    'redemption_price' => $priceData['redemption_price'] > 0 ? $priceData['redemption_price'] : null,
                    'is_current' => true,
                    'effective_date' => now(),
                ]
            );
        }
    }

    private function getCurrency(string $code): Currency
    {
        $currency = Currency::query()->where('code', $code)->first();

        throw_unless($currency instanceof Currency, RuntimeException::class, $code.' currency not found. Please run CurrencySeeder first.');

        return $currency;
    }

    private function createOrGetTld(array $priceData): Tld
    {
        $tldType = match ($priceData['type']) {
            'local' => TldType::Local,
            'international' => TldType::International,
        };

        $tldStatus = match ($priceData['status']) {
            'active' => TldStatus::Active,
            'inactive' => TldStatus::Inactive,
        };

        return Tld::query()->firstOrCreate(
            ['name' => $priceData['tld']],
            [
                'uuid' => Str::uuid()->toString(),
                'name' => $priceData['tld'],
                'type' => $tldType,
                'status' => $tldStatus,
            ]
        );
    }

    private function getCurrencyIdForType(string $type, Currency $usdCurrency, Currency $rwfCurrency): int
    {
        return match ($type) {
            'local' => $rwfCurrency->id,
            'international' => $usdCurrency->id,
        };
    }
}
