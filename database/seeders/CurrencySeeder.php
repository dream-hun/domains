<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'uuid' => Str::uuid(),
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'RWF',
                'name' => 'Rwandan Franc',
                'symbol' => 'FRW',
                'is_base' => true,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'is_base' => false,
                'is_active' => false,
            ],
        ];
        Currency::query()->insert($currencies);
    }
}
