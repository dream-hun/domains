<?php

declare(strict_types=1);

use App\Models\HostingCategory;
use App\Models\HostingPlan;
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
        Permission::query()->firstOrCreate(['id' => 200, 'title' => 'hosting_plan_access'])->id,
        Permission::query()->firstOrCreate(['id' => 201, 'title' => 'hosting_plan_create'])->id,
        Permission::query()->firstOrCreate(['id' => 202, 'title' => 'hosting_plan_edit'])->id,
        Permission::query()->firstOrCreate(['id' => 203, 'title' => 'hosting_plan_delete'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]);
});

it('displays hosting plans on index page', function (): void {
    $category = HostingCategory::factory()->create(['name' => 'Shared Hosting']);
    HostingPlan::factory()->create(['name' => 'Basic Plan', 'category_id' => $category->id]);
    HostingPlan::factory()->create(['name' => 'Pro Plan', 'category_id' => $category->id]);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-plans');

    $response->assertSuccessful();
    $response->assertSee('Basic Plan');
    $response->assertSee('Pro Plan');
});

it('can filter hosting plans by category', function (): void {
    $category1 = HostingCategory::factory()->create(['name' => 'Shared']);
    $category2 = HostingCategory::factory()->create(['name' => 'VPS']);

    HostingPlan::factory()->create(['name' => 'Shared Plan', 'category_id' => $category1->id]);
    HostingPlan::factory()->create(['name' => 'VPS Plan', 'category_id' => $category2->id]);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-plans?category_id='.$category1->id);

    $response->assertSuccessful();
    $response->assertSee('Shared Plan');
    $response->assertDontSee('VPS Plan');
});
