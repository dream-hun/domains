<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin role and permissions
    $adminRole = Role::query()->firstOrCreate(['title' => 'Admin']);
    $userRole = Role::query()->firstOrCreate(['title' => 'User']);

    $subscriptionCreatePermission = Permission::query()->firstOrCreate(['title' => 'subscription_create']);
    $subscriptionAccessPermission = Permission::query()->firstOrCreate(['title' => 'subscription_access']);

    $adminRole->permissions()->syncWithoutDetaching([
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

    // Verify order was created
    $order = Order::query()->where('user_id', $this->regularUser->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('custom_subscription')
        ->and($order->payment_status)->toBe('manual')
        ->and($order->status)->toBe('completed')
        ->and(abs((float) $order->total_amount - 75.50))->toBeLessThan(0.01)
        ->and($order->currency)->toBe('USD')
        ->and($order->metadata['created_by_admin_id'])->toBe($this->admin->id)
        ->and($order->metadata['subscription_id'])->toBe($subscription->id);

    // Verify order item was created
    expect($order->orderItems)->toHaveCount(1);
    $orderItem = $order->orderItems->first();
    expect($orderItem->domain_name)->toBe('Premium Plan')
        ->and($orderItem->domain_type)->toBe('custom_subscription')
        ->and(abs((float) $orderItem->price - 75.50))->toBeLessThan(0.01);
});

test('admin can create custom subscription with zero price', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Free Plan']);
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 0,
    ]);

    $data = [
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'custom_price' => 0,
        'custom_price_currency' => 'USD',
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

    expect($subscription)->not->toBeNull();

    // Verify order was still created
    $order = Order::query()->where('user_id', $this->regularUser->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('custom_subscription')
        ->and((float) $order->total_amount)->toBe(0.0);
});

test('admin can create custom subscription with custom price in non-USD currency', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'RWF Plan']);
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
        ->and(abs((float) $subscription->custom_price - 120000.0))->toBeLessThan(0.01);

    // Verify order was created with RWF currency
    $order = Order::query()->where('user_id', $this->regularUser->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->currency)->toBe('RWF')
        ->and(abs((float) $order->total_amount - 120000.0))->toBeLessThan(0.01);
});

test('validation requires custom price and currency', function (): void {
    $plan = HostingPlan::factory()->create();
    HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $data = [
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        // Missing custom_price and custom_price_currency
        'billing_cycle' => 'monthly',
        'starts_at' => now()->format('Y-m-d'),
        'expires_at' => now()->addMonth()->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.store'), $data)
        ->assertSessionHasErrors(['custom_price', 'custom_price_currency']);
});

test('validation requires valid user and hosting plan', function (): void {
    $data = [
        'user_id' => 99999, // Non-existent user
        'hosting_plan_id' => 99999, // Non-existent plan
        'custom_price' => 50.00,
        'custom_price_currency' => 'USD',
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
        ->assertSee('Test Plan');

    // Verify subscription was created with custom price
    expect($subscription->is_custom_price)->toBeTrue()
        ->and((float) $subscription->custom_price)->toBe(50.00);
});
