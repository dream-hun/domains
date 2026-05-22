<?php

declare(strict_types=1);

use App\Http\Middleware\AuthGates;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

function createRoleAdminUser(): User
{
    $role = Role::query()->create(['title' => 'RoleAdmin-'.uniqid()]);
    foreach (['role_access', 'role_create', 'role_edit', 'role_show', 'role_delete'] as $perm) {
        $role->permissions()->attach(
            Permission::query()->where('title', $perm)->first()?->id
                ?? Permission::query()->create(['title' => $perm])->id
        );
    }

    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

function setupRoleAdminGates(User $user): void
{
    auth()->login($user);
    (new AuthGates)->handle(Request::create('/'), fn ($req) => $req);
}

it('renders the create role page with permissions options', function (): void {
    $user = createRoleAdminUser();
    setupRoleAdminGates($user);

    $permission = Permission::query()->create(['title' => 'some_permission']);

    $response = $this->actingAs($user)->get(route('admin.roles.create'));

    $response->assertOk();
    $response->assertSee($permission->title);
});

it('renders select-all and deselect-all buttons outside the label on create page', function (): void {
    $user = createRoleAdminUser();
    setupRoleAdminGates($user);

    $response = $this->actingAs($user)->get(route('admin.roles.create'));

    $response->assertOk();
    $response->assertSee('select-all', false);
    $response->assertSee('deselect-all', false);
    $response->assertSee('padding-bottom: 4px', false);
});

it('stores a new role with permissions', function (): void {
    $user = createRoleAdminUser();
    setupRoleAdminGates($user);

    $permission = Permission::query()->create(['title' => 'test_perm_'.uniqid()]);

    $response = $this->actingAs($user)->post(route('admin.roles.store'), [
        'title' => 'Test Role',
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('title', 'Test Role')->first();
    expect($role)->not->toBeNull()
        ->and($role->permissions->contains($permission->id))->toBeTrue();
});

it('renders the edit role page with pre-selected permissions', function (): void {
    $user = createRoleAdminUser();
    setupRoleAdminGates($user);

    $permission = Permission::query()->create(['title' => 'edit_perm_'.uniqid()]);
    $role = Role::query()->create(['title' => 'Editable Role']);
    $role->permissions()->attach($permission->id);

    $response = $this->actingAs($user)->get(route('admin.roles.edit', $role));

    $response->assertOk();
    $response->assertSee($permission->title);
    $response->assertSee('selected', false);
});

it('renders select-all and deselect-all buttons outside the label on edit page', function (): void {
    $user = createRoleAdminUser();
    setupRoleAdminGates($user);

    $role = Role::query()->create(['title' => 'Some Role']);

    $response = $this->actingAs($user)->get(route('admin.roles.edit', $role));

    $response->assertOk();
    $response->assertSee('select-all', false);
    $response->assertSee('deselect-all', false);
    $response->assertSee('padding-bottom: 4px', false);
});

it('updates a role with new permissions', function (): void {
    $user = createRoleAdminUser();
    setupRoleAdminGates($user);

    $oldPermission = Permission::query()->create(['title' => 'old_perm_'.uniqid()]);
    $newPermission = Permission::query()->create(['title' => 'new_perm_'.uniqid()]);
    $role = Role::query()->create(['title' => 'Role To Update']);
    $role->permissions()->attach($oldPermission->id);

    $response = $this->actingAs($user)->put(route('admin.roles.update', $role), [
        'title' => 'Updated Role',
        'permissions' => [$newPermission->id],
    ]);

    $response->assertRedirect(route('admin.roles.index'));

    $role->refresh()->load('permissions');
    expect($role->title)->toBe('Updated Role')
        ->and($role->permissions->contains($newPermission->id))->toBeTrue()
        ->and($role->permissions->contains($oldPermission->id))->toBeFalse();
});
