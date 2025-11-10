<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DomainPrice;
use Illuminate\Database\Seeder;

final class FixRwandanDomainPricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Fix Rwandan domain prices to be in RWF instead of USD
     */
    public function run(): void
    {
        // Rwandan domains should be priced in RWF
        // $12 USD ≈ 16,200 RWF (at 1350 RWF per USD)
        $rwandanDomains = DomainPrice::query()->where('type', 'local')->get();

        foreach ($rwandanDomains as $domain) {
            $domain->update([
                'register_price' => 1620000, // 16,200 RWF in cents
                'renewal_price' => 1620000,
                'transfer_price' => 1620000,
                'redemption_price' => 8775000, // 87,750 RWF in cents (equivalent to $65 USD)
            ]);
        }

        $this->command->info('Rwandan domain prices updated to RWF.');
    }
}
