<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sharedPermissions = [
            'vps_access',
            'vps_show',
            'vps_restart',
            'vps_shutdown',
            'vps_reinstall',
            'vps_rescue',
            'vps_reset_credentials',
            'vps_change_display_name',
            'vps_snapshot_access',
            'vps_snapshot_create',
            'vps_snapshot_delete',
            'vps_backup_access',
            'vps_backup_restore',
            'vps_vnc_access',
            'vps_upgrade',
        ];

        $adminOnlyPermissions = [
            'vps_cancel',
            'vps_assign',
            'vps_order_license',
            'vps_move_region',
            'vps_extend_storage',
        ];

        $allPermissions = array_merge($sharedPermissions, $adminOnlyPermissions);

        $rows = array_map(fn (string $title): array => [
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ], $allPermissions);

        Permission::query()->upsert($rows, ['title'], ['updated_at']);

        $adminRole = Role::query()->where('title', 'Admin')->first();
        $userRole = Role::query()->where('title', 'User')->first();

        if ($adminRole) {
            $allPermissionIds = Permission::query()
                ->whereIn('title', $allPermissions)
                ->pluck('id');

            $adminRole->permissions()->syncWithoutDetaching($allPermissionIds);
        }

        if ($userRole) {
            $sharedPermissionIds = Permission::query()
                ->whereIn('title', $sharedPermissions)
                ->pluck('id');

            $userRole->permissions()->syncWithoutDetaching($sharedPermissionIds);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $allPermissions = [
            'vps_access', 'vps_show', 'vps_restart', 'vps_shutdown', 'vps_reinstall',
            'vps_rescue', 'vps_reset_credentials', 'vps_change_display_name',
            'vps_snapshot_access', 'vps_snapshot_create', 'vps_snapshot_delete',
            'vps_backup_access', 'vps_backup_restore', 'vps_vnc_access', 'vps_upgrade',
            'vps_cancel', 'vps_assign', 'vps_order_license', 'vps_move_region', 'vps_extend_storage',
        ];

        $permissionIds = Permission::query()->whereIn('title', $allPermissions)->pluck('id');

        Illuminate\Support\Facades\DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        Permission::query()->whereIn('title', $allPermissions)->delete();
    }
};
