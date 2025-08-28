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
            [
                'id' => 1,
                'title' => 'user_management_access',
            ],
            [
                'id' => 2,
                'title' => 'permission_create',
            ],
            [
                'id' => 3,
                'title' => 'permission_edit',
            ],
            [
                'id' => 4,
                'title' => 'permission_show',
            ],
            [
                'id' => 5,
                'title' => 'permission_delete',
            ],
            [
                'id' => 6,
                'title' => 'permission_access',
            ],
            [
                'id' => 7,
                'title' => 'role_create',
            ],
            [
                'id' => 8,
                'title' => 'role_edit',
            ],
            [
                'id' => 9,
                'title' => 'role_show',
            ],
            [
                'id' => 10,
                'title' => 'role_delete',
            ],
            [
                'id' => 11,
                'title' => 'role_access',
            ],
            [
                'id' => 12,
                'title' => 'user_create',
            ],
            [
                'id' => 13,
                'title' => 'user_edit',
            ],
            [
                'id' => 14,
                'title' => 'user_show',
            ],
            [
                'id' => 15,
                'title' => 'user_delete',
            ],
            [
                'id' => 16,
                'title' => 'user_access',
            ],
            [
                'id' => 17,
                'title' => 'domain_pricing_create',
            ],
            [
                'id' => 18,
                'title' => 'domain_pricing_edit',
            ],
            [
                'id' => 19,
                'title' => 'domain_pricing_delete',
            ],
            [
                'id' => 20,
                'title' => 'domain_pricing_access',
            ],
            [
                'id' => 21,
                'title' => 'contact_create',
            ],
            [
                'id' => 22,
                'title' => 'contact_edit',
            ],
            [
                'id' => 23,
                'title' => 'contact_show',
            ],
            [
                'id' => 24,
                'title' => 'contact_delete',
            ],
            [
                'id' => 25,
                'title' => 'contact_access',
            ],
            [
                'id' => 26,
                'title' => 'domain_edit',
            ],
            [
                'id' => 27,
                'title' => 'domain_show',
            ],
            [
                'id' => 28,
                'title' => 'domain_delete',
            ],
            [
                'id' => 29,
                'title' => 'domain_access',
            ],
            [
                'id' => 30,
                'title' => 'dns_record_create',
            ],
            [
                'id' => 31,
                'title' => 'dns_record_edit',
            ],
            [
                'id' => 32,
                'title' => 'dns_record_show',
            ],
            [
                'id' => 33,
                'title' => 'dns_record_delete',
            ],
            [
                'id' => 34,
                'title' => 'dns_record_access',
            ],
            [
                'id' => 35,
                'title' => 'setting_create',
            ],
            [
                'id' => 36,
                'title' => 'setting_edit',
            ],
            [
                'id' => 37,
                'title' => 'setting_show',
            ],
            [
                'id' => 38,
                'title' => 'setting_delete',
            ],
            [
                'id' => 39,
                'title' => 'setting_access',
            ],
            [
                'id' => 40,
                'title' => 'nameserver_create',
            ],
            [
                'id' => 41,
                'title' => 'nameserver_edit',
            ],
            [
                'id' => 42,
                'title' => 'nameserver_show',
            ],
            [
                'id' => 43,
                'title' => 'nameserver_delete',
            ],
            [
                'id' => 44,
                'title' => 'nameserver_access',
            ],
            [
                'id' => 45,
                'title' => 'profile_password_edit',
            ],
            [
                'id' => 46,
                'title' => 'hosting_create',

            ],
            [
                'id' => 47,
                'title' => 'hosting_edit',
            ],
            [
                'id' => 48,
                'title' => 'hosting_show',
            ],
            [
                'id' => 49,
                'title' => 'hosting_delete',
            ],
            [
                'id' => 50,
                'title' => 'hosting_access',
            ],
        ];

        Permission::insert($permissions);
    }
}
