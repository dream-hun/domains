<?php

declare(strict_types=1);

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    // Create both necessary roles (user model automatically attaches role id 2)
    Role::query()->firstOrCreate(['id' => 1, 'title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2, 'title' => 'User']);

    // Create admin user
    $this->admin = User::factory()->create();

    // Attach admin role and permissions
    $adminRole = Role::query()->find(1);
    $permissionIds = [
        Permission::query()->firstOrCreate(['id' => 100, 'title' => 'hosting_plan_price_create'])->id,
        Permission::query()->firstOrCreate(['id' => 101, 'title' => 'hosting_plan_price_edit'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]); // Detach default role 2, attach admin role 1

    // Create a hosting category and plan for testing
    $this->category = HostingCategory::factory()->create();
    $this->plan = HostingPlan::factory()->create(['category_id' => $this->category->id]);
});

it('displays hosting plan prices on index page', function (): void {
    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2999,
    ]);
    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'annually',
        'regular_price' => 29999,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-plan-prices');

    $response->assertStatus(200);
    $response->assertSee('Hosting Plan Prices');
});

it('allows admin to view create page', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/hosting-plan-prices/create');

    $response->assertStatus(200);
    $response->assertSee('Add New Price');
});

it('allows admin to create a new hosting plan price', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-prices', [
        'hosting_category_id' => $this->category->id,
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2999,
        'renewal_price' => 2999,
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/hosting-plan-prices');
    $response->assertSessionHas('success', 'Hosting plan price created successfully.');

    $this->assertDatabaseHas('hosting_plan_prices', [
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2999,
        'renewal_price' => 2999,
        'status' => 'active',
    ]);
});

it('validates required fields when creating hosting plan price', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-prices', []);

    $response->assertSessionHasErrors([
        'hosting_category_id',
        'hosting_plan_id',
        'billing_cycle',
        'regular_price',
        'renewal_price',
    ]);
});

it('validates billing cycle is valid', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-prices', [
        'hosting_category_id' => $this->category->id,
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'invalid-cycle',
        'regular_price' => 2999,
        'renewal_price' => 2999,
    ]);

    $response->assertSessionHasErrors('billing_cycle');
});

it('validates prices are non-negative integers', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-prices', [
        'hosting_category_id' => $this->category->id,
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => -100,
        'renewal_price' => 2999,
    ]);

    $response->assertSessionHasErrors('regular_price');
});

it('validates hosting plan belongs to the selected category', function (): void {
    $otherCategory = HostingCategory::factory()->create();
    $otherPlan = HostingPlan::factory()->create(['category_id' => $otherCategory->id]);

    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-prices', [
        'hosting_category_id' => $this->category->id,
        'hosting_plan_id' => $otherPlan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2999,
        'renewal_price' => 2999,
    ]);

    $response->assertSessionHasErrors('hosting_plan_id');
});

it('allows admin to view edit page', function (): void {
    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
    ]);

    $response = $this->actingAs($this->admin)->get(sprintf('/admin/hosting-plan-prices/%s/edit', $price->uuid));

    $response->assertStatus(200);
    $response->assertSee('Edit Hosting Plan Price');
});

it('allows admin to update a hosting plan price', function (): void {
    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2999,
        'renewal_price' => 2999,
    ]);

    $response = $this->actingAs($this->admin)->put('/admin/hosting-plan-prices/'.$price->uuid, [
        'hosting_category_id' => $this->category->id,
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'annually',
        'regular_price' => 29999,
        'renewal_price' => 29999,
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/hosting-plan-prices');
    $response->assertSessionHas('success', 'Hosting plan price updated successfully.');

    $this->assertDatabaseHas('hosting_plan_prices', [
        'uuid' => $price->uuid,
        'billing_cycle' => 'annually',
        'regular_price' => 29999,
        'renewal_price' => 29999,
    ]);
});

it('allows admin to delete a hosting plan price', function (): void {
    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
    ]);

    $response = $this->actingAs($this->admin)->delete('/admin/hosting-plan-prices/'.$price->uuid);

    $response->assertRedirect('/admin/hosting-plan-prices');
    $response->assertSessionHas('success', 'Hosting plan price deleted successfully.');

    $this->assertDatabaseMissing('hosting_plan_prices', [
        'uuid' => $price->uuid,
    ]);
});

it('creates price with default status when not provided', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-prices', [
        'hosting_category_id' => $this->category->id,
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2999,
        'renewal_price' => 2999,
    ]);

    $response->assertRedirect('/admin/hosting-plan-prices');

    $this->assertDatabaseHas('hosting_plan_prices', [
        'hosting_plan_id' => $this->plan->id,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);
});

it('requires authentication to access hosting plan prices', function (): void {
    $this->get('/admin/hosting-plan-prices')->assertRedirect('/login');
});
