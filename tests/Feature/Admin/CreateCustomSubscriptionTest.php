<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin role and permissions
    $adminRole = Role::query()->create(['id' => 1, 'title' => 'Admin']);
    $userRole = Role::query()->create(['id' => 2, 'title' => 'User']);

    $subscriptionCreatePermission = Permission::query()->create(['id' => 88, 'title' => 'subscription_create']);
    $subscriptionAccessPermission = Permission::query()->create(['id' => 92, 'title' => 'subscription_access']);

    $adminRole->permissions()->attach([
        $subscriptionCreatePermission->id,
        $subscriptionAccessPermission->id,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach($adminRole);

    $this->regularUser = User::factory()->create();
    $this->regularUser->roles()->attach($userRole);

    // Create currencies
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Currency::query()->create([
        'code' => 'RWF',
        'name' => 'Rwandan Franc',
        'symbol' => 'Fr',
        'exchange_rate' => 1200.0,
        'is_base' => false,
        'is_active' => true,
    ]);
});

test('admin can access create custom subscription form', function (): void {
    $plan = HostingPlan::factory()->create();
    HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id, 'billing_cycle' => 'monthly']);

    $this->actingAs($this->admin)
        ->get(route('admin.subscriptions.create'))
        ->assertSuccessful()
        ->assertSee('Create Custom Subscription');
});

test('non-admin cannot access create custom subscription form', function (): void {
    $this->actingAs($this->regularUser)
        ->get(route('admin.subscriptions.create'))
        ->assertForbidden();
});

test('admin can create custom subscription with custom price', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Premium Plan']);
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000, // $100.00 in cents
    ]);

    $data = [
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'custom_price' => 75.50,
        'custom_price_currency' => 'USD',
        'billing_cycle' => 'monthly',
        'domain' => 'testdomain.com',
        'starts_at' => now()->format('Y-m-d'),
        'expires_at' => now()->addMonth()->format('Y-m-d'),
        'auto_renew' => true,
        'custom_price_notes' => 'Special pricing for VIP customer',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $subscription = Subscription::query()
        ->where('user_id', $this->regularUser->id)
        ->where('hosting_plan_id', $plan->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->is_custom_price)->toBeTrue()
        ->and(abs((float) $subscription->custom_price - 75.50))->toBeLessThan(0.01)
        ->and($subscription->custom_price_currency)->toBe('USD')
        ->and($subscription->created_by_admin_id)->toBe($this->admin->id)
        ->and($subscription->custom_price_notes)->toBe('Special pricing for VIP customer')
        ->and($subscription->domain)->toBe('testdomain.com')
        ->and($subscription->auto_renew)->toBeTrue();
});

test('admin can create custom subscription without custom price', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $data = [
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'starts_at' => now()->format('Y-m-d'),
        'expires_at' => now()->addMonth()->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $subscription = Subscription::query()
        ->where('user_id', $this->regularUser->id)
        ->where('hosting_plan_id', $plan->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->is_custom_price)->toBeFalse()
        ->and($subscription->custom_price)->toBeNull();
});

test('admin can create custom subscription with custom price and currency conversion', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
    ]);

    // RWF 1200 = USD 1.00, so RWF 120000 = USD 100.00
    $data = [
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'custom_price' => 120000,
        'custom_price_currency' => 'RWF',
        'billing_cycle' => 'monthly',
        'starts_at' => now()->format('Y-m-d'),
        'expires_at' => now()->addMonth()->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $subscription = Subscription::query()
        ->where('user_id', $this->regularUser->id)
        ->where('hosting_plan_id', $plan->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->is_custom_price)->toBeTrue()
        ->and($subscription->custom_price_currency)->toBe('RWF')
        ->and($subscription->custom_price)->toBeGreaterThan(0); // Should be converted to USD
});

test('validation requires currency when custom price is provided', function (): void {
    $plan = HostingPlan::factory()->create();
    HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $data = [
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'custom_price' => 100.00,
        // Missing custom_price_currency
        'billing_cycle' => 'monthly',
        'starts_at' => now()->format('Y-m-d'),
        'expires_at' => now()->addMonth()->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.store'), $data)
        ->assertSessionHasErrors('custom_price_currency');
});

test('validation requires valid user and hosting plan', function (): void {
    $data = [
        'user_id' => 99999, // Non-existent user
        'hosting_plan_id' => 99999, // Non-existent plan
        'billing_cycle' => 'monthly',
        'starts_at' => now()->format('Y-m-d'),
        'expires_at' => now()->addMonth()->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.store'), $data)
        ->assertSessionHasErrors(['user_id', 'hosting_plan_id']);
});

test('custom subscription appears in subscription list', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Test Plan']);
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'is_custom_price' => true,
        'custom_price' => 50.00,
        'custom_price_currency' => 'USD',
        'created_by_admin_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.subscriptions.index'))
        ->assertSuccessful()
        ->assertSee('Test Plan')
        ->assertSee('50.00');
});
