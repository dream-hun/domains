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
        Role::query()->findOrFail(1)->permissions()->sync($admin_permissions->pluck('id'));
        $user_permissions = $admin_permissions->reject(fn ($permission): bool => in_array(mb_substr((string) $permission->title, 0, 5), ['user_', 'role_', 'permission_'], true));
        Role::query()->findOrFail(2)->permissions()->sync($user_permissions);

    }
}
