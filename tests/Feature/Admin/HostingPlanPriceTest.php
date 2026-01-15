<?php

declare(strict_types=1);

use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::query()->firstOrCreate(['id' => 1, 'title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2, 'title' => 'User']);

    $this->admin = User::factory()->create();

    $adminRole = Role::query()->find(1);
    $permissionIds = [
        Permission::query()->firstOrCreate(['id' => 104, 'title' => 'hosting_plan_price_access'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]);
});

it('displays hosting plan prices on index page', function (): void {
    HostingPlanPrice::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)->get('/admin/hosting-plan-prices');

    $response->assertSuccessful();
});

it('can search hosting plan prices across pages', function (): void {
    $plan1 = HostingPlan::factory()->create(['name' => 'UniquePlanName']);
    HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan1->id]);

    // Create many other prices to force pagination
    HostingPlanPrice::factory()->count(20)->create();

    // Search for the unique plan name which might be on a later page
    $response = $this->actingAs($this->admin)->get('/admin/hosting-plan-prices?search=UniquePlanName');

    $response->assertSuccessful();
    $response->assertSee('UniquePlanName');
    // Ensure we don't see all records if they don't match (though we only have one that matches)
});
