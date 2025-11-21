<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['title' => 'audit_log_access'],
            [
                'title' => 'audit_log_access',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')
            ->where('title', 'audit_log_access')
            ->value('id');

        $adminRoleExists = Schema::hasTable('roles')
            && DB::table('roles')->where('id', 1)->exists();

        if (! $permissionId || ! Schema::hasTable('permission_role') || ! $adminRoleExists) {
            return;
        }

        DB::table('permission_role')->updateOrInsert(
            [
                'permission_id' => $permissionId,
                'role_id' => 1,
            ],
            [
                'permission_id' => $permissionId,
                'role_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permission = DB::table('permissions')
            ->where('title', 'audit_log_access')
            ->first();

        if (! $permission) {
            return;
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')
                ->where('permission_id', $permission->id)
                ->delete();
        }

        DB::table('permissions')
            ->where('id', $permission->id)
            ->delete();
    }
};
