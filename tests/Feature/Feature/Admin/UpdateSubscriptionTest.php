<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createSubscriptionAdmin(string $permission = 'subscription_edit'): User
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

test('edit page loads with billing cycle and currency options', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.subscriptions.edit', $subscription));

    $response->assertOk();
    $response->assertViewIs('admin.subscriptions.edit');
    $response->assertViewHas('billingCycleOptions');
    $response->assertViewHas('currencies');
});

test('update subscription with custom price fields', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create();
    $currency = Currency::query()->where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'domain' => 'example.com',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'custom_price' => 29.99,
        'custom_price_currency' => 'USD',
        'custom_price_notes' => 'Special rate for VIP customer',
    ]);

    $response->assertRedirect(route('admin.subscriptions.show', $subscription));
    $response->assertSessionHas('success');

    $subscription->refresh();
    expect($subscription->billing_cycle)->toBe('monthly')
        ->and((float) $subscription->custom_price)->toBe(29.99)
        ->and($subscription->custom_price_currency)->toBe('USD')
        ->and($subscription->is_custom_price)->toBeTrue()
        ->and($subscription->custom_price_notes)->toBe('Special rate for VIP customer');
});

test('update subscription with billing cycle change to annually', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create(['billing_cycle' => 'monthly']);

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2027-01-01T00:00',
        'billing_cycle' => 'annually',
    ]);

    $response->assertRedirect(route('admin.subscriptions.show', $subscription));

    $subscription->refresh();
    expect($subscription->billing_cycle)->toBe('annually');
});

test('clearing custom price sets is_custom_price to false', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create([
        'custom_price' => 50.00,
        'custom_price_currency' => 'EUR',
        'is_custom_price' => true,
        'custom_price_notes' => 'Old note',
    ]);

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'billing_cycle' => 'monthly',
        'custom_price' => null,
        'custom_price_currency' => null,
        'custom_price_notes' => null,
    ]);

    $response->assertRedirect(route('admin.subscriptions.show', $subscription));

    $subscription->refresh();
    expect($subscription->is_custom_price)->toBeFalse()
        ->and($subscription->custom_price)->toBeNull()
        ->and($subscription->custom_price_currency)->toBeNull();
});

test('validation requires currency when custom price is provided', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'billing_cycle' => 'monthly',
        'custom_price' => 29.99,
        'custom_price_currency' => null,
    ]);

    $response->assertSessionHasErrors('custom_price_currency');
});

test('validation requires valid billing cycle', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'billing_cycle' => 'weekly',
    ]);

    $response->assertSessionHasErrors('billing_cycle');
});

test('setting status to cancelled populates cancelled_at', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create(['status' => 'active', 'cancelled_at' => null]);

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'cancelled',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect(route('admin.subscriptions.show', $subscription));

    $subscription->refresh();
    expect($subscription->status)->toBe('cancelled')
        ->and($subscription->cancelled_at)->not->toBeNull();
});

test('setting status to active clears cancelled_at', function (): void {
    $user = createSubscriptionAdmin();
    $subscription = Subscription::factory()->create(['status' => 'cancelled', 'cancelled_at' => now()]);

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect(route('admin.subscriptions.show', $subscription));

    $subscription->refresh();
    expect($subscription->status)->toBe('active')
        ->and($subscription->cancelled_at)->toBeNull();
});

test('update returns 403 without subscription_edit permission', function (): void {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.subscriptions.update', $subscription), [
        'status' => 'active',
        'starts_at' => '2026-01-01T00:00',
        'expires_at' => '2026-02-01T00:00',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertForbidden();
});
