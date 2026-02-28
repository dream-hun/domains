<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

beforeEach(function (): void {
    foreach (Role::cases() as $role) {
        Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => $role->value]);
    }
});

test('registration screen can be rendered', function (): void {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('newly registered users are assigned the player role', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    /** @var User $user */
    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user->hasRole(Role::Player))->toBeTrue();
});
