<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\AllocationConfiguration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    AllocationConfiguration::query()->create([
        'insurance_percentage' => 25.0,
        'savings_percentage' => 25.0,
        'pathway_percentage' => 25.0,
        'administration_percentage' => 25.0,
    ]);
});

test('guest is redirected from allocation configuration edit', function (): void {
    $this->get(route('admin.allocation-configuration.edit'))
        ->assertRedirect(route('login'));
});

test('player cannot access allocation configuration', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->get(route('admin.allocation-configuration.edit'))
        ->assertForbidden();
});

test('admin can view allocation configuration edit page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->get(route('admin.allocation-configuration.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/allocation-configuration/edit')
            ->has('config.insurance_percentage')
            ->has('config.savings_percentage')
            ->has('config.pathway_percentage')
            ->has('config.administration_percentage')
        );
});

test('admin can update allocation configuration', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 40.0,
        'savings_percentage' => 30.0,
        'pathway_percentage' => 20.0,
        'administration_percentage' => 10.0,
    ])->assertRedirect(route('admin.allocation-configuration.edit'));

    $this->assertDatabaseHas('allocation_configurations', [
        'insurance_percentage' => 40.0,
        'savings_percentage' => 30.0,
        'pathway_percentage' => 20.0,
        'administration_percentage' => 10.0,
        'updated_by' => $admin->id,
    ]);
});

test('old config row is preserved when saving new configuration', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $originalCount = AllocationConfiguration::query()->count();

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 40.0,
        'savings_percentage' => 30.0,
        'pathway_percentage' => 20.0,
        'administration_percentage' => 10.0,
    ]);

    expect(AllocationConfiguration::query()->count())->toBe($originalCount + 1);
});

test('percentages must sum to 100', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 30.0,
        'savings_percentage' => 30.0,
        'pathway_percentage' => 30.0,
        'administration_percentage' => 30.0,
    ])->assertInvalid(['insurance_percentage']);
});

test('validation rejects missing percentages', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->patch(route('admin.allocation-configuration.update'), [])
        ->assertInvalid([
            'insurance_percentage',
            'savings_percentage',
            'pathway_percentage',
            'administration_percentage',
        ]);
});

test('validation rejects percentages above 100', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 101,
        'savings_percentage' => 101,
        'pathway_percentage' => 101,
        'administration_percentage' => 101,
    ])->assertInvalid([
        'insurance_percentage',
        'savings_percentage',
        'pathway_percentage',
        'administration_percentage',
    ]);
});

test('validation rejects negative percentages', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => -1,
        'savings_percentage' => -1,
        'pathway_percentage' => -1,
        'administration_percentage' => -1,
    ])->assertInvalid([
        'insurance_percentage',
        'savings_percentage',
        'pathway_percentage',
        'administration_percentage',
    ]);
});

test('guest cannot update allocation configuration', function (): void {
    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 25.0,
        'savings_percentage' => 25.0,
        'pathway_percentage' => 25.0,
        'administration_percentage' => 25.0,
    ])->assertRedirect(route('login'));
});

test('player cannot update allocation configuration', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 25.0,
        'savings_percentage' => 25.0,
        'pathway_percentage' => 25.0,
        'administration_percentage' => 25.0,
    ])->assertForbidden();
});

test('update redirects with success flash message', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->patch(route('admin.allocation-configuration.update'), [
        'insurance_percentage' => 40.0,
        'savings_percentage' => 30.0,
        'pathway_percentage' => 20.0,
        'administration_percentage' => 10.0,
    ])->assertSessionHas('success');
});
