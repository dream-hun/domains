<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_assign_permission()
    {
        $user = User::factory()->create();
        $permission = Permission::create(['title' => 'domain_show']);

        $user->permissions()->attach($permission);

        $this->assertTrue($user->permissions->contains($permission));
    }
}
