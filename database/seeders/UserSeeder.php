<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id' => 1,
                'uuid' => Str::uuid()->toString(),
                'client_code' => 'BLCL-000001',
                'first_name' => 'Jacques',
                'last_name' => 'MBABAZI',
                'email' => 'mbabazijacques@gmail.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'remember_token' => null,
            ],
            [
                'id' => 2,
                'uuid' => Str::uuid()->toString(),
                'client_code' => 'BLCL-000002',
                'first_name' => 'Jean Paul',
                'last_name' => 'TURIKUMWE',
                'email' => 'minisimbi@gmail.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'remember_token' => null,
            ],
        ];
        User::query()->insert($users);
    }
}
