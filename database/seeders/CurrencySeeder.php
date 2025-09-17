<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

final class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.000000,
                'is_base' => true,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
            [
                'code' => 'RWF',
                'name' => 'Rwandan Franc',
                'symbol' => 'FRw',
                'exchange_rate' => 1350.000000, // Approximate rate
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 0.920000, // Approximate rate
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'exchange_rate' => 0.790000, // Approximate rate
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
            [
                'code' => 'KES',
                'name' => 'Kenyan Shilling',
                'symbol' => 'KSh',
                'exchange_rate' => 145.000000, // Approximate rate
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
            [
                'code' => 'UGX',
                'name' => 'Ugandan Shilling',
                'symbol' => 'USh',
                'exchange_rate' => 3700.000000, // Approximate rate
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
            [
                'code' => 'TZS',
                'name' => 'Tanzanian Shilling',
                'symbol' => 'TSh',
                'exchange_rate' => 2500.000000, // Approximate rate
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
