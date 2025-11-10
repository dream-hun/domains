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
                'symbol' => 'FRW',
                'exchange_rate' => 1350.000000,
                'is_base' => false,
                'is_active' => true,
                'rate_updated_at' => now(),
            ],

        ];

        Currency::query()->insert($currencies);

        $this->command->info('Currencies seeded successfully.');
    }
}
