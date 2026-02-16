<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

final class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Domain::query()->create([
            'uuid' => Str::uuid(),
            'name' => 'blutest.co.rw',
            'auth_code' => 'AL4HTCJI8x5NU0MA',
            'years' => 4,
            'status' => 'active',
            'auto_renew' => false,
            'is_premium' => false,
            'is_locked' => false,
            'registered_at' => Date::parse('2025-02-28T00:00:00Z'),
            'expires_at' => Date::parse('2029-02-28T00:00:00Z'),
            'last_renewed_at' => Date::parse('2025-04-17T10:26:15Z'),
            'tld_pricing_id' => 1,
            'owner_id' => 1,
        ]);
    }
}
