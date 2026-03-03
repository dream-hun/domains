<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\RankingConfiguration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

// SuperAdmin access
test('super admin can access courts', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)->get(route('admin.courts.index'))->assertOk();
});

test('super admin can access games', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)->get(route('admin.games.index'))->assertOk();
});

test('super admin can access users', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
});

test('super admin can access moderation', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)->get(route('admin.moderation.index'))->assertOk();
});

test('super admin can access ranking', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)->get(route('admin.ranking.edit'))->assertOk();
});

// Administrator access
test('administrator can access courts', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($user)->get(route('admin.courts.index'))->assertOk();
});

test('administrator can access games', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($user)->get(route('admin.games.index'))->assertOk();
});

test('administrator cannot access users', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

test('administrator can access moderation', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($user)->get(route('admin.moderation.index'))->assertOk();
});

test('administrator can access ranking', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($user)->get(route('admin.ranking.edit'))->assertOk();
});

test('super admin can access override', function (): void {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)->get(route('admin.override.index'))->assertOk();
});

test('administrator can access override', function (): void {
    $user = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($user)->get(route('admin.override.index'))->assertOk();
});

// Moderator access
test('moderator can access moderation', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);

    $this->actingAs($user)->get(route('admin.moderation.index'))->assertOk();
});

test('moderator cannot access courts', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);

    $this->actingAs($user)->get(route('admin.courts.index'))->assertForbidden();
});

test('moderator cannot access ranking', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);

    $this->actingAs($user)->get(route('admin.ranking.edit'))->assertForbidden();
});

test('moderator cannot access override', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);

    $this->actingAs($user)->get(route('admin.override.index'))->assertForbidden();
});

test('moderator cannot access users', function (): void {
    $user = User::factory()->create()->assignRole(Role::Moderator->value);

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

// Player access
test('player can access games', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)->get(route('admin.games.index'))->assertOk();
});

test('player cannot access courts', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)->get(route('admin.courts.index'))->assertForbidden();
});

test('player cannot access moderation', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)->get(route('admin.moderation.index'))->assertForbidden();
});

test('player cannot access users', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

test('player cannot access ranking', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)->get(route('admin.ranking.edit'))->assertForbidden();
});

test('player cannot access override', function (): void {
    $user = User::factory()->create()->assignRole(Role::Player->value);

    $this->actingAs($user)->get(route('admin.override.index'))->assertForbidden();
});
