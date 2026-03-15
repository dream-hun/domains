<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Domain;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createDomainAdmin(string $permission = 'domain_edit'): User
{
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'Admin-'.uniqid()]);
    $role->permissions()->attach(
        Permission::query()->where('title', $permission)->first()?->id
            ?? Permission::query()->create(['title' => $permission])->id
    );
    $user->roles()->attach($role);

    return $user;
}

test('edit registration page loads successfully', function (): void {
    $user = createDomainAdmin();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.domains.edit-registration', $domain));

    $response->assertOk();
    $response->assertViewIs('admin.domains.edit-custom');
    $response->assertViewHas('domain');
    $response->assertViewHas('users');
    $response->assertViewHas('currencies');
    $response->assertViewHas('domainStatuses');
});

test('update domain registration with all fields', function (): void {
    $user = createDomainAdmin();
    $domain = Domain::factory()->create();
    $newOwner = User::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $response = $this->actingAs($user)->put(route('admin.domains.update-registration', $domain), [
        'owner_id' => $newOwner->id,
        'years' => 3,
        'status' => 'active',
        'auto_renew' => true,
        'registered_at' => '2025-01-01',
        'expires_at' => '2028-01-01',
        'custom_price' => 15.99,
        'custom_price_currency' => 'USD',
        'custom_price_notes' => 'Discounted renewal',
    ]);

    $response->assertRedirect(route('admin.domains.info', $domain));
    $response->assertSessionHas('success');

    $domain->refresh();
    expect($domain->owner_id)->toBe($newOwner->id)
        ->and($domain->years)->toBe(3)
        ->and($domain->status->value)->toBe('active')
        ->and($domain->auto_renew)->toBeTrue()
        ->and((float) $domain->custom_price)->toBe(15.99)
        ->and($domain->custom_price_currency)->toBe('USD')
        ->and($domain->is_custom_price)->toBeTrue()
        ->and($domain->custom_price_notes)->toBe('Discounted renewal');
});

test('update domain registration without custom price', function (): void {
    $user = createDomainAdmin();
    $domain = Domain::factory()->create([
        'custom_price' => 20.00,
        'custom_price_currency' => 'EUR',
        'is_custom_price' => true,
    ]);

    $response = $this->actingAs($user)->put(route('admin.domains.update-registration', $domain), [
        'owner_id' => $domain->owner_id,
        'years' => 1,
        'status' => 'active',
        'registered_at' => '2025-01-01',
        'expires_at' => '2026-01-01',
        'custom_price' => null,
        'custom_price_currency' => null,
    ]);

    $response->assertRedirect(route('admin.domains.info', $domain));

    $domain->refresh();
    expect($domain->is_custom_price)->toBeFalse()
        ->and($domain->custom_price)->toBeNull();
});

test('validation fails with invalid status', function (): void {
    $user = createDomainAdmin();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.domains.update-registration', $domain), [
        'owner_id' => $domain->owner_id,
        'years' => 1,
        'status' => 'invalid_status',
        'registered_at' => '2025-01-01',
        'expires_at' => '2026-01-01',
    ]);

    $response->assertSessionHasErrors('status');
});

test('validation fails when expires_at is before registered_at', function (): void {
    $user = createDomainAdmin();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.domains.update-registration', $domain), [
        'owner_id' => $domain->owner_id,
        'years' => 1,
        'status' => 'active',
        'registered_at' => '2026-01-01',
        'expires_at' => '2025-01-01',
    ]);

    $response->assertSessionHasErrors('expires_at');
});

test('validation requires currency when custom price is provided', function (): void {
    $user = createDomainAdmin();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.domains.update-registration', $domain), [
        'owner_id' => $domain->owner_id,
        'years' => 1,
        'status' => 'active',
        'registered_at' => '2025-01-01',
        'expires_at' => '2026-01-01',
        'custom_price' => 10.00,
        'custom_price_currency' => null,
    ]);

    $response->assertSessionHasErrors('custom_price_currency');
});

test('edit registration returns 403 without domain_edit permission', function (): void {
    $user = User::factory()->create();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.domains.edit-registration', $domain));

    $response->assertForbidden();
});

test('update registration returns 403 without domain_edit permission', function (): void {
    $user = User::factory()->create();
    $domain = Domain::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.domains.update-registration', $domain), [
        'owner_id' => $domain->owner_id,
        'years' => 1,
        'status' => 'active',
        'registered_at' => '2025-01-01',
        'expires_at' => '2026-01-01',
    ]);

    $response->assertForbidden();
});
