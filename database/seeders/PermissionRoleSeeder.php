<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class PermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin_permissions = Permission::all();
        Role::findOrFail(1)->permissions()->sync($admin_permissions->pluck('id'));
        $user_permissions = $admin_permissions->filter(function ($permission) {
            return mb_substr($permission->title, 0, 5) !== 'user_' && mb_substr($permission->title, 0, 5) !== 'role_' && mb_substr($permission->title, 0, 5) !== 'permission_';

        });
        Role::findOrFail(2)->permissions()->sync($user_permissions);

    }
}
