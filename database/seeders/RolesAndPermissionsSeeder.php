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

        // SuperAdmin: no explicit permissions — Gate::before handles it
        SpatieRole::findByName(Role::SuperAdmin->value)
            ->syncPermissions([]);

        // Administrator: all permissions except ViewUsers and ManageUsers
        $adminPermissions = array_filter(
            Permission::cases(),
            fn (Permission $p): bool => ! in_array($p, [Permission::ViewUsers, Permission::ManageUsers], true),
        );

        SpatieRole::findByName(Role::Administrator->value)
            ->syncPermissions(array_map(fn (Permission $p) => $p->value, $adminPermissions));

        // Moderator: moderation queue only
        SpatieRole::findByName(Role::Moderator->value)
            ->syncPermissions([
                Permission::ViewGames->value,
                Permission::ModerateGames->value,
            ]);

        // Player: games + leaderboard
        SpatieRole::findByName(Role::Player->value)
            ->syncPermissions([
                Permission::ViewGames->value,
                Permission::CreateGames->value,
            ]);
    }
}
