<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DomainPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DomainPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prices = [
            [
                'uuid' => Str::uuid(),
                'tld' => '.com',
                'type' => 'international',
                'register_price' => 18000,
                'renewal_price' => 20000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.org',
                'type' => 'international',
                'register_price' => 18000,
                'renewal_price' => 20000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.net',
                'type' => 'international',
                'register_price' => 14000,
                'renewal_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.cloud',
                'type' => 'international',
                'register_price' => 15000,
                'renewal_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.io',
                'type' => 'international',
                'register_price' => 60000,
                'renewal_price' => 70000,
                'transfer_price' => 30000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.app',
                'type' => 'international',
                'register_price' => 20000,
                'renewal_price' => 22000,
                'transfer_price' => 12000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.dev',
                'type' => 'international',
                'register_price' => 20000,
                'renewal_price' => 22000,
                'transfer_price' => 12000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.tech',
                'type' => 'international',
                'register_price' => 15000,
                'renewal_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.xyz',
                'type' => 'international',
                'register_price' => 5000,
                'renewal_price' => 8000,
                'transfer_price' => 5000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.me',
                'type' => 'international',
                'register_price' => 15000,
                'renewal_price' => 16000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.rw',
                'type' => 'local',
                'register_price' => 10000,
                'renewal_price' => 12000,
                'transfer_price' => 8000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.co.rw',
                'type' => 'local',
                'register_price' => 15000,
                'renewal_price' => 17000,
                'transfer_price' => 12000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.org.rw',
                'type' => 'local',
                'register_price' => 12000,
                'renewal_price' => 14000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.net.rw',
                'type' => 'local',
                'register_price' => 12000,
                'renewal_price' => 14000,
                'transfer_price' => 10000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'tld' => '.ac.rw',
                'type' => 'local',
                'register_price' => 20000,
                'renewal_price' => 22000,
                'transfer_price' => 15000,
                'redemption_price' => 0,
                'min_years' => 1,
                'max_years' => 10,
                'status' => 'active',
            ],

        ];

        foreach ($prices as $price) {
            DomainPrice::updateOrCreate(
                ['tld' => $price['tld']],
                $price
            );
        }
    }
}
