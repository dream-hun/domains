<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DomainPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DomainPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * All prices are in Rwandan Francs (RWF).
     */
    public function run(): void
    {
        // Define all TLDs to be seeded with their base registration and redemption prices in RWF
        $tlds = [
            // --- Local (.rw) TLDs ---
            ['uuid' => Str::uuid(), 'tld' => 'rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],
            ['uuid' => Str::uuid(), 'tld' => 'co.rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],
            ['uuid' => Str::uuid(), 'tld' => 'org.rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],
            ['uuid' => Str::uuid(), 'tld' => 'ac.rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],
            ['uuid' => Str::uuid(), 'tld' => 'gov.rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],
            ['uuid' => Str::uuid(), 'tld' => 'mil.rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],
            ['uuid' => Str::uuid(), 'tld' => 'net.rw', 'type' => 'local', 'price' => 15000, 'redemption' => 85000],

            // --- Standard International TLDs ---
            ['uuid' => Str::uuid(), 'tld' => 'com', 'type' => 'international', 'price' => 20000, 'redemption' => 120000],
            ['uuid' => Str::uuid(), 'tld' => 'net', 'type' => 'international', 'price' => 22000, 'redemption' => 130000],
            ['uuid' => Str::uuid(), 'tld' => 'org', 'type' => 'international', 'price' => 21000, 'redemption' => 125000],
            ['uuid' => Str::uuid(), 'tld' => 'info', 'type' => 'international', 'price' => 25000, 'redemption' => 140000],
            ['uuid' => Str::uuid(), 'tld' => 'biz', 'type' => 'international', 'price' => 24000, 'redemption' => 135000],
            ['uuid' => Str::uuid(), 'tld' => 'co', 'type' => 'international', 'price' => 35000, 'redemption' => 180000],

            // --- Technology & Business TLDs ---
            ['uuid' => Str::uuid(), 'tld' => 'io', 'type' => 'international', 'price' => 65000, 'redemption' => 250000],
            ['uuid' => Str::uuid(), 'tld' => 'ai', 'type' => 'international', 'price' => 90000, 'redemption' => 300000],
            ['uuid' => Str::uuid(), 'tld' => 'dev', 'type' => 'international', 'price' => 25000, 'redemption' => 150000],
            ['uuid' => Str::uuid(), 'tld' => 'app', 'type' => 'international', 'price' => 28000, 'redemption' => 160000],
            ['uuid' => Str::uuid(), 'tld' => 'tech', 'type' => 'international', 'price' => 55000, 'redemption' => 200000],
            ['uuid' => Str::uuid(), 'tld' => 'software', 'type' => 'international', 'price' => 40000, 'redemption' => 180000],
            ['uuid' => Str::uuid(), 'tld' => 'system', 'type' => 'international', 'price' => 30000, 'redemption' => 170000],
            ['uuid' => Str::uuid(), 'tld' => 'cloud', 'type' => 'international', 'price' => 32000, 'redemption' => 175000],
            ['uuid' => Str::uuid(), 'tld' => 'digital', 'type' => 'international', 'price' => 29000, 'redemption' => 160000],
            ['uuid' => Str::uuid(), 'tld' => 'agency', 'type' => 'international', 'price' => 26000, 'redemption' => 150000],
            ['uuid' => Str::uuid(), 'tld' => 'solutions', 'type' => 'international', 'price' => 31000, 'redemption' => 165000],
            ['uuid' => Str::uuid(), 'tld' => 'enterprises', 'type' => 'international', 'price' => 34000, 'redemption' => 175000],
            ['uuid' => Str::uuid(), 'tld' => 'company', 'type' => 'international', 'price' => 20000, 'redemption' => 120000],
            ['uuid' => Str::uuid(), 'tld' => 'industries', 'type' => 'international', 'price' => 38000, 'redemption' => 190000],

            ['uuid' => Str::uuid(), 'tld' => 'xyz', 'type' => 'international', 'price' => 12000, 'redemption' => 90000],
            ['uuid' => Str::uuid(), 'tld' => 'online', 'type' => 'international', 'price' => 18000, 'redemption' => 110000],
            ['uuid' => Str::uuid(), 'tld' => 'site', 'type' => 'international', 'price' => 17000, 'redemption' => 105000],
            ['uuid' => Str::uuid(), 'tld' => 'website', 'type' => 'international', 'price' => 16000, 'redemption' => 100000],
            ['uuid' => Str::uuid(), 'tld' => 'space', 'type' => 'international', 'price' => 15000, 'redemption' => 95000],
            ['uuid' => Str::uuid(), 'tld' => 'icu', 'type' => 'international', 'price' => 10000, 'redemption' => 80000],
            ['uuid' => Str::uuid(), 'tld' => 'top', 'type' => 'international', 'price' => 9000, 'redemption' => 75000],
            ['uuid' => Str::uuid(), 'tld' => 'link', 'type' => 'international', 'price' => 14000, 'redemption' => 95000],
            ['uuid' => Str::uuid(), 'tld' => 'click', 'type' => 'international', 'price' => 13000, 'redemption' => 90000],

            ['uuid' => Str::uuid(), 'tld' => 'art', 'type' => 'international', 'price' => 25000, 'redemption' => 140000],
            ['uuid' => Str::uuid(), 'tld' => 'design', 'type' => 'international', 'price' => 45000, 'redemption' => 180000],
            ['uuid' => Str::uuid(), 'tld' => 'photography', 'type' => 'international', 'price' => 30000, 'redemption' => 160000],
            ['uuid' => Str::uuid(), 'tld' => 'studio', 'type' => 'international', 'price' => 32000, 'redemption' => 165000],
            ['uuid' => Str::uuid(), 'tld' => 'media', 'type' => 'international', 'price' => 38000, 'redemption' => 170000],
            ['uuid' => Str::uuid(), 'tld' => 'live', 'type' => 'international', 'price' => 33000, 'redemption' => 170000],
            ['uuid' => Str::uuid(), 'tld' => 'life', 'type' => 'international', 'price' => 30000, 'redemption' => 160000],
            ['uuid' => Str::uuid(), 'tld' => 'world', 'type' => 'international', 'price' => 20000, 'redemption' => 120000],
            ['uuid' => Str::uuid(), 'tld' => 'today', 'type' => 'international', 'price' => 24000, 'redemption' => 130000],
            ['uuid' => Str::uuid(), 'tld' => 'style', 'type' => 'international', 'price' => 36000, 'redemption' => 175000],
            ['uuid' => Str::uuid(), 'tld' => 'family', 'type' => 'international', 'price' => 28000, 'redemption' => 150000],

            ['uuid' => Str::uuid(), 'tld' => 'shop', 'type' => 'international', 'price' => 35000, 'redemption' => 170000],
            ['uuid' => Str::uuid(), 'tld' => 'store', 'type' => 'international', 'price' => 40000, 'redemption' => 180000],
            ['uuid' => Str::uuid(), 'tld' => 'market', 'type' => 'international', 'price' => 38000, 'redemption' => 175000],
            ['uuid' => Str::uuid(), 'tld' => 'sale', 'type' => 'international', 'price' => 36000, 'redemption' => 170000],

            ['uuid' => Str::uuid(), 'tld' => 'shop', 'type' => 'international', 'price' => 35000, 'redemption' => 170000],
            ['uuid' => Str::uuid(), 'tld' => 'store', 'type' => 'international', 'price' => 40000, 'redemption' => 180000],
            ['uuid' => Str::uuid(), 'tld' => 'market', 'type' => 'international', 'price' => 38000, 'redemption' => 175000],
            ['uuid' => Str::uuid(), 'tld' => 'sale', 'type' => 'international', 'price' => 36000, 'redemption' => 170000],

            ['uuid' => Str::uuid(), 'tld' => 'shop', 'type' => 'international', 'price' => 35000, 'redemption' => 170000],
            ['uuid' => Str::uuid(), 'tld' => 'store', 'type' => 'international', 'price' => 40000, 'redemption' => 180000],
            ['uuid' => Str::uuid(), 'tld' => 'market', 'type' => 'international', 'price' => 38000, 'redemption' => 175000],
            ['uuid' => Str::uuid(), 'tld' => 'sale', 'type' => 'international', 'price' => 36000, 'redemption' => 170000],

            ['uuid' => Str::uuid(), 'tld' => 'africa', 'type' => 'international', 'price' => 25000, 'redemption' => 140000],
            ['uuid' => Str::uuid(), 'tld' => 'paris', 'type' => 'international', 'price' => 45000, 'redemption' => 190000],
            ['uuid' => Str::uuid(), 'tld' => 'london', 'type' => 'international', 'price' => 50000, 'redemption' => 200000],

            // --- Other Country Code (ccTLDs) ---
            ['uuid' => Str::uuid(), 'tld' => 'ug', 'type' => 'international', 'price' => 30000, 'redemption' => 150000],
            ['uuid' => Str::uuid(), 'tld' => 'ke', 'type' => 'international', 'price' => 30000, 'redemption' => 150000],
            ['uuid' => Str::uuid(), 'tld' => 'tz', 'type' => 'international', 'price' => 32000, 'redemption' => 155000],
            ['uuid' => Str::uuid(), 'tld' => 'za', 'type' => 'international', 'price' => 20000, 'redemption' => 120000],
            ['uuid' => Str::uuid(), 'tld' => 'ng', 'type' => 'international', 'price' => 22000, 'redemption' => 130000],
            ['uuid' => Str::uuid(), 'tld' => 'us', 'type' => 'international', 'price' => 18000, 'redemption' => 110000],
            ['uuid' => Str::uuid(), 'tld' => 'uk', 'type' => 'international', 'price' => 16000, 'redemption' => 100000],
            ['uuid' => Str::uuid(), 'tld' => 'ca', 'type' => 'international', 'price' => 20000, 'redemption' => 120000],
            ['uuid' => Str::uuid(), 'tld' => 'de', 'type' => 'international', 'price' => 15000, 'redemption' => 95000],
        ];
        DomainPrice::updateOrCreate($tlds);

    }
}
