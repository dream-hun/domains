<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Nameserver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class NameserverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nameservers = [
            [
                'uuid' => Str::uuid(),
                'name' => 'ns1.bluhub.com',
                'type' => 'default',
                'ipv4' => '127.0.0.1',
                'ipv6' => '::1',
                'priority' => 1,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'ns2.bluhub.com',
                'type' => 'default',
                'ipv4' => '127.0.0.1',
                'ipv6' => '::1',
                'priority' => 1,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'ns3.bluhub.com',
                'type' => 'default',
                'ipv4' => '127.0.0.1',
                'ipv6' => '::1',
                'priority' => 1,
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'ns4.bluhub.com',
                'type' => 'default',
                'ipv4' => '127.0.0.1',
                'ipv6' => '::1',
                'priority' => 1,
                'status' => 'active',
            ],
        ];
        Nameserver::insert($nameservers);
    }
}
