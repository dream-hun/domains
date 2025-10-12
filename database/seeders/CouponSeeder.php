<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('coupons')->insert([
            [
                'uuid' => Str::uuid(),
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10,
                'max_uses' => 100,
                'uses' => 0,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addMonths(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'FREESHIP',
                'type' => 'fixed',
                'value' => 5,
                'max_uses' => 50,
                'uses' => 0,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'HOLIDAY25',
                'type' => 'percentage',
                'value' => 25,
                'max_uses' => 200,
                'uses' => 0,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
