<?php

declare(strict_types=1);

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

    $subscriptionAccessPermission = Permission::query()->create(['id' => 92, 'title' => 'subscription_access']);
    $subscriptionShowPermission = Permission::query()->create(['id' => 90, 'title' => 'subscription_show']);
    $subscriptionEditPermission = Permission::query()->create(['id' => 89, 'title' => 'subscription_edit']);

    $adminRole->permissions()->attach([
        $subscriptionAccessPermission->id,
        $subscriptionShowPermission->id,
        $subscriptionEditPermission->id,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach($adminRole);

    $this->regularUser = User::factory()->create();
    $this->regularUser->roles()->attach($userRole);
});

test('admin can view subscription list', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Test Plan']);
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.subscriptions.index'))
        ->assertSuccessful()
        ->assertSee('Test Plan');
});

test('non-admin cannot access subscription list', function (): void {
    $this->actingAs($this->regularUser)
        ->get(route('admin.subscriptions.index'))
        ->assertForbidden();
});

test('admin can view individual subscription', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Premium Plan']);
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'domain' => 'testdomain.com',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.subscriptions.show', $subscription))
        ->assertSuccessful()
        ->assertSee('Premium Plan')
        ->assertSee('testdomain.com');
});

test('admin can edit subscription', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.subscriptions.edit', $subscription))
        ->assertSuccessful()
        ->assertSee('Edit Subscription');
});

test('admin can update subscription status', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.subscriptions.update', $subscription), [
            'status' => 'suspended',
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'expires_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'next_renewal_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'domain' => 'updated-domain.com',
            'auto_renew' => false,
        ])
        ->assertRedirect(route('admin.subscriptions.show', $subscription));

    expect($subscription->fresh()->status)->toBe('suspended')
        ->and($subscription->fresh()->domain)->toBe('updated-domain.com');
});

test('admin can manually renew subscription', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $originalExpiry = now()->addDays(5);
    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'expires_at' => $originalExpiry,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.subscriptions.renew-now', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->expires_at)->toBeGreaterThan($originalExpiry);
});

test('non-admin cannot edit subscriptions', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
    ]);

    $this->actingAs($this->regularUser)
        ->get(route('admin.subscriptions.edit', $subscription))
        ->assertForbidden();
});

test('non-admin cannot update subscriptions', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

    $subscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($this->regularUser)
        ->put(route('admin.subscriptions.update', $subscription), [
            'status' => 'suspended',
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'expires_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        ])
        ->assertForbidden();
});
