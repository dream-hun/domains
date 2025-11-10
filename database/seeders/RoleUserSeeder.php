<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->findOrFail(1)->roles()->sync(1);
        User::query()->findOrFail(2)->roles()->sync(2);
    }
}
