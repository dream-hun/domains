<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $administrators = [
            ['name' => 'Admin One', 'email' => 'admin@bouncepurse.test'],
            ['name' => 'Admin Two', 'email' => 'admin2@bouncepurse.test'],
        ];

        foreach ($administrators as $data) {
            User::factory()->create($data)->assignRole(Role::Administrator->value);
        }

        $moderators = [
            ['name' => 'Moderator One', 'email' => 'moderator@bouncepurse.test'],
            ['name' => 'Moderator Two', 'email' => 'moderator2@bouncepurse.test'],
            ['name' => 'Moderator Three', 'email' => 'moderator3@bouncepurse.test'],
        ];

        foreach ($moderators as $data) {
            User::factory()->create($data)->assignRole(Role::Moderator->value);
        }
    }
}
