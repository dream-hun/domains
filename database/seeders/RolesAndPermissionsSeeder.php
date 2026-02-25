<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            SpatiePermission::query()->firstOrCreate(['name' => $permission->value]);
        }

        foreach (Role::cases() as $role) {
            SpatieRole::query()->firstOrCreate(['name' => $role->value]);
        }

        SpatieRole::findByName(Role::Administrator->value)
            ->syncPermissions(Permission::values());

        SpatieRole::findByName(Role::Moderator->value)
            ->syncPermissions([
                Permission::ViewCourts->value,
                Permission::ViewGames->value,
                Permission::ModerateGames->value,
                Permission::EditGames->value,
            ]);

        SpatieRole::findByName(Role::Player->value)
            ->syncPermissions([
                Permission::ViewCourts->value,
                Permission::ViewGames->value,
                Permission::CreateGames->value,
            ]);
    }
}
