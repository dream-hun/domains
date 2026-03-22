<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add vps_start permission
        Permission::query()->upsert([
            ['title' => 'vps_start', 'created_at' => now(), 'updated_at' => now()],
        ], ['title'], ['updated_at']);

        $startPermission = Permission::query()->where('title', 'vps_start')->first();

        $adminRole = Role::query()->where('title', 'Admin')->first();
        $userRole = Role::query()->where('title', 'User')->first();

        // Grant vps_start to both Admin and User roles
        if ($startPermission) {
            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching([$startPermission->id]);
            }

            if ($userRole) {
                $userRole->permissions()->syncWithoutDetaching([$startPermission->id]);
            }
        }

        // Remove vps_upgrade from User role (admin-only)
        $upgradePermission = Permission::query()->where('title', 'vps_upgrade')->first();
        if ($upgradePermission && $userRole) {
            $userRole->permissions()->detach($upgradePermission->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $userRole = Role::query()->where('title', 'User')->first();

        // Re-attach vps_upgrade to User role
        $upgradePermission = Permission::query()->where('title', 'vps_upgrade')->first();
        if ($upgradePermission && $userRole) {
            $userRole->permissions()->syncWithoutDetaching([$upgradePermission->id]);
        }

        // Remove vps_start permission
        $startPermission = Permission::query()->where('title', 'vps_start')->first();
        if ($startPermission) {
            DB::table('permission_role')->where('permission_id', $startPermission->id)->delete();
            $startPermission->delete();
        }
    }
};
