<?php

declare(strict_types=1);

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tld;
use App\Models\TldPricing;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('index requires authentication', function (): void {
    $response = $this->get(route('admin.tld-pricings.index'));

    $response->assertRedirect(route('login'));
});

test('index returns 403 without tld_pricing_access permission', function (): void {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('admin.tld-pricings.index'));

    $response->assertForbidden();
});

test('index returns 200 with tld_pricing_access permission', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'Admin']);
    $role->permissions()->attach(
        Permission::query()->whereIn('title', [
            'tld_pricing_access',
            'tld_pricing_create',
            'tld_pricing_edit',
            'tld_pricing_delete',
        ])->pluck('id')
    );
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('admin.tld-pricings.index'));

    $response->assertOk();
    $response->assertViewIs('admin.tld-pricing.index');
    $response->assertViewHas('tldPricings');
});

test('store creates tld pricing and redirects', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'Admin']);
    $role->permissions()->attach(
        Permission::query()->where('title', 'tld_pricing_create')->first()?->id ?? Permission::query()->create(['title' => 'tld_pricing_create'])->id
    );
    $user->roles()->attach($role);

    $tld = Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Active,
    ]);
    $currency = Currency::query()->first() ?? Currency::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.tld-pricings.store'), [
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'register_price' => 10,
        'renew_price' => 12,
        'redemption_price' => 0,
        'transfer_price' => 0,
        'is_current' => true,
        'effective_date' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('admin.tld-pricings.index'));
    $response->assertSessionHas('success');

    expect(TldPricing::query()->count())->toBe(1);
    $pricing = TldPricing::query()->first();
    expect($pricing->tld_id)->toBe($tld->id)
        ->and($pricing->currency_id)->toBe($currency->id)
        ->and($pricing->register_price)->toBe(10)
        ->and($pricing->renew_price)->toBe(12)
        ->and($pricing->is_current)->toBeTrue();
});

test('update modifies tld pricing and redirects', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'Admin']);
    $role->permissions()->attach(
        Permission::query()->where('title', 'tld_pricing_edit')->first()?->id ?? Permission::query()->create(['title' => 'tld_pricing_edit'])->id
    );
    $user->roles()->attach($role);

    $currency = Currency::query()->first() ?? Currency::factory()->create();
    $tldPricing = TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => null,
        'currency_id' => $currency->id,
        'register_price' => 5,
        'renew_price' => 6,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $response = $this->actingAs($user)->put(route('admin.tld-pricings.update', $tldPricing), [
        'tld_id' => '',
        'currency_id' => $currency->id,
        'register_price' => 6,
        'renew_price' => 7,
        'redemption_price' => 0,
        'transfer_price' => 0,
        'is_current' => true,
        'effective_date' => $tldPricing->effective_date->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('admin.tld-pricings.index'));
    $response->assertSessionHas('success');

    $tldPricing->refresh();
    expect($tldPricing->register_price)->toBe(6)
        ->and($tldPricing->renew_price)->toBe(7);
});

test('destroy deletes tld pricing and redirects', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'Admin']);
    $role->permissions()->attach(
        Permission::query()->where('title', 'tld_pricing_delete')->first()?->id ?? Permission::query()->create(['title' => 'tld_pricing_delete'])->id
    );
    $user->roles()->attach($role);

    $currency = Currency::query()->first() ?? Currency::factory()->create();
    $tldPricing = TldPricing::query()->create([
        'uuid' => (string) Str::uuid(),
        'tld_id' => null,
        'currency_id' => $currency->id,
        'register_price' => 10,
        'renew_price' => 10,
        'redemption_price' => null,
        'transfer_price' => null,
        'is_current' => true,
        'effective_date' => now(),
    ]);

    $response = $this->actingAs($user)->delete(route('admin.tld-pricings.destroy', $tldPricing));

    $response->assertRedirect(route('admin.tld-pricings.index'));
    $response->assertSessionHas('success');

    expect(TldPricing::query()->find($tldPricing->id))->toBeNull();
});
