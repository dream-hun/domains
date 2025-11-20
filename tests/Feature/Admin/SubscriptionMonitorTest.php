<?php

declare(strict_types=1);

use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $adminRole = Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2], ['title' => 'User']);

    $this->admin = User::factory()->create();
    $this->admin->roles()->sync([$adminRole->id]);

    $subscriptionAccess = Permission::query()->firstOrCreate(
        ['id' => 92],
        ['title' => 'subscription_access']
    );

    $adminRole->permissions()->syncWithoutDetaching([$subscriptionAccess->id]);

    $this->plan = HostingPlan::factory()->create();
    $this->planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
    ]);
});

it('allows an admin to monitor subscriptions with filters applied', function (): void {
    $subscription = Subscription::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $this->admin->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'product_snapshot' => [
            'plan' => $this->plan->name,
            'price' => $this->planPrice->regular_price,
        ],
        'billing_cycle' => 'monthly',
        'domain' => 'example-monitor.com',
        'status' => 'active',
        'starts_at' => now()->subDays(5),
        'expires_at' => now()->addDays(25),
        'next_renewal_at' => now()->addDays(25),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.subscriptions.index', [
        'status' => 'active',
        'search' => 'example-monitor.com',
        'billing_cycle' => 'monthly',
    ]));

    $response->assertOk();
    $response->assertSee('Subscription Monitor');
    $response->assertSee($subscription->domain);
    $response->assertSee($this->plan->name);
});
