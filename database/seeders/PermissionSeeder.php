<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['id' => 1, 'title' => 'user_management_access'],
            ['id' => 2, 'title' => 'permission_create'],
            ['id' => 3, 'title' => 'permission_edit'],
            ['id' => 4, 'title' => 'permission_show'],
            ['id' => 5, 'title' => 'permission_delete'],
            ['id' => 6, 'title' => 'permission_access'],
            ['id' => 7, 'title' => 'role_create'],
            ['id' => 8, 'title' => 'role_edit'],
            ['id' => 9, 'title' => 'role_show'],
            ['id' => 10, 'title' => 'role_delete'],
            ['id' => 11, 'title' => 'role_access'],
            ['id' => 12, 'title' => 'user_create'],
            ['id' => 13, 'title' => 'user_edit'],
            ['id' => 14, 'title' => 'user_show'],
            ['id' => 15, 'title' => 'user_delete'],
            ['id' => 16, 'title' => 'user_access'],
            ['id' => 17, 'title' => 'domain_edit'],
            ['id' => 18, 'title' => 'domain_show'],
            ['id' => 19, 'title' => 'domain_delete'],
            ['id' => 20, 'title' => 'domain_renew'],
            ['id' => 21, 'title' => 'domain_transfer'],
            ['id' => 22, 'title' => 'domain_access'],
            ['id' => 23, 'title' => 'domain_pricing_create'],
            ['id' => 24, 'title' => 'domain_pricing_edit'],
            ['id' => 25, 'title' => 'domain_pricing_delete'],
            ['id' => 26, 'title' => 'domain_pricing_access'],
            ['id' => 27, 'title' => 'contact_create'],
            ['id' => 28, 'title' => 'contact_edit'],
            ['id' => 29, 'title' => 'contact_show'],
            ['id' => 30, 'title' => 'contact_delete'],
            ['id' => 31, 'title' => 'contact_access'],
            ['id' => 32, 'title' => 'setting_create'],
            ['id' => 33, 'title' => 'setting_edit'],
            ['id' => 34, 'title' => 'setting_show'],
            ['id' => 35, 'title' => 'setting_delete'],
            ['id' => 36, 'title' => 'setting_access'],
            ['id' => 37, 'title' => 'nameserver_create'],
            ['id' => 38, 'title' => 'nameserver_edit'],
            ['id' => 39, 'title' => 'nameserver_show'],
            ['id' => 40, 'title' => 'nameserver_delete'],
            ['id' => 41, 'title' => 'nameserver_access'],
            ['id' => 42, 'title' => 'profile_password_edit'],
            ['id' => 43, 'title' => 'hosting_create'],
            ['id' => 44, 'title' => 'hosting_edit'],
            ['id' => 45, 'title' => 'hosting_show'],
            ['id' => 46, 'title' => 'hosting_delete'],
            ['id' => 47, 'title' => 'hosting_access'],
            ['id' => 48, 'title' => 'currency_access'],
            ['id' => 49, 'title' => 'currency_create'],
            ['id' => 50, 'title' => 'currency_edit'],
            ['id' => 51, 'title' => 'currency_delete'],
            ['id' => 52, 'title' => 'currency_update_rates'],
            ['id' => 53, 'title' => 'failed_registration_access'],
            ['id' => 54, 'title' => 'failed_registration_retry'],
        ];

        Permission::insert($permissions);
    }
}
