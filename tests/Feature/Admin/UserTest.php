<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected from users index', function (): void {
    $response = $this->get(route('admin.users.index'));
    $response->assertRedirect(route('login'));
});

test('administrators can view users index', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->get(route('admin.users.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/users/index'));
});

test('non-administrators cannot view users index', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $response = $this->get(route('admin.users.index'));
    $response->assertForbidden();
});

test('administrators can create a user', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.users.store'), [
        'name' => 'New Player',
        'email' => 'newplayer@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => Role::Player->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New Player',
        'email' => 'newplayer@example.com',
    ]);

    $newUser = User::query()->where('email', 'newplayer@example.com')->first();
    expect($newUser->hasRole(Role::Player->value))->toBeTrue();
});

test('create user validates required fields', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.users.store'), []);

    $response->assertInvalid(['name', 'email', 'password', 'role']);
});

test('administrators can update a user role', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'role' => Role::Moderator->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->hasRole(Role::Moderator->value))->toBeTrue();
    expect($user->hasRole(Role::Player->value))->toBeFalse();
});

test('administrators can delete a user', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $user = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($admin);

    $response = $this->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('administrators cannot delete themselves', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $response = $this->delete(route('admin.users.destroy', $admin));

    $response->assertInvalid(['user']);

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

test('users index can be filtered by search', function (): void {
    $admin = User::factory()->create()->assignRole(Role::SuperAdmin->value);
    $this->actingAs($admin);

    $matching = User::factory()->create(['name' => 'Alice Wonderland'])->assignRole(Role::Player->value);
    User::factory()->create(['name' => 'Bob Builder'])->assignRole(Role::Player->value);

    $response = $this->get(route('admin.users.index', ['search' => 'Alice']));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/users/index')
            ->where('filters.search', 'Alice')
            ->has('users.data', 1)
            ->where('users.data.0.id', $matching->id)
    );
});
