<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DomainPrice;
use Illuminate\Database\Seeder;

final class UpdateDomainPricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Convert all prices from dollars to cents if they're not already in cents
     */
    public function run(): void
    {
        $domainPrices = DomainPrice::all();

        foreach ($domainPrices as $domainPrice) {
            // Check if prices are likely in dollars (less than 1000 suggests dollars, not cents)
            if ($domainPrice->register_price < 1000) {
                $domainPrice->update([
                    'register_price' => $domainPrice->register_price * 100,
                    'renewal_price' => $domainPrice->renewal_price * 100,
                    'transfer_price' => $domainPrice->transfer_price * 100,
                    'redemption_price' => $domainPrice->redemption_price * 100,
                ]);
            }
        }

        $this->command->info('Domain prices updated to cents format.');
    }
}
