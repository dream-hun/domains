<?php

declare(strict_types=1);

use App\Enums\Hosting\HostingPlanStatus;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createPlanAdmin(array $permissions): User
{
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'PlanAdmin']);
    $role->permissions()->attach(
        Permission::query()->whereIn('title', $permissions)->pluck('id')
    );
    $user->roles()->attach($role);

    return $user;
}

function validPlanData(array $overrides = []): array
{
    $category = HostingCategory::factory()->create();

    return array_merge([
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'description' => 'A test hosting plan',
        'tagline' => 'Best plan ever',
        'is_popular' => 1,
        'status' => HostingPlanStatus::Active->value,
        'sort_order' => 1,
        'category_id' => $category->id,
        'contabo_product_id' => null,
    ], $overrides);
}

// --- Store ---

test('store creates a hosting plan with contabo_product_id', function (): void {
    $user = createPlanAdmin(['hosting_plan_create']);

    $data = validPlanData(['contabo_product_id' => 'V45']);

    $response = $this->actingAs($user)
        ->post(route('admin.hosting-plans.store'), $data);

    $response->assertRedirect(route('admin.hosting-plans.index'));
    $response->assertSessionHas('success');

    $plan = HostingPlan::query()->where('slug', 'test-plan')->first();
    expect($plan)->not->toBeNull()
        ->and($plan->name)->toBe('Test Plan')
        ->and($plan->contabo_product_id)->toBe('V45');
});

test('store creates a hosting plan without contabo_product_id', function (): void {
    $user = createPlanAdmin(['hosting_plan_create']);

    $data = validPlanData(['contabo_product_id' => null]);

    $response = $this->actingAs($user)
        ->post(route('admin.hosting-plans.store'), $data);

    $response->assertRedirect(route('admin.hosting-plans.index'));

    $plan = HostingPlan::query()->where('slug', 'test-plan')->first();
    expect($plan)->not->toBeNull()
        ->and($plan->contabo_product_id)->toBeNull();
});

// --- Update ---

test('update sets contabo_product_id on an existing plan', function (): void {
    $user = createPlanAdmin(['hosting_plan_edit']);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create([
        'category_id' => $category->id,
        'contabo_product_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->put(route('admin.hosting-plans.update', $plan), [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'tagline' => $plan->tagline,
            'is_popular' => $plan->is_popular ? 1 : 0,
            'status' => $plan->status->value,
            'sort_order' => $plan->sort_order,
            'category_id' => $category->id,
            'contabo_product_id' => 'V50',
        ]);

    $response->assertRedirect(route('admin.hosting-plans.index'));

    $plan->refresh();
    expect($plan->contabo_product_id)->toBe('V50');
});

test('update clears contabo_product_id when set to empty', function (): void {
    $user = createPlanAdmin(['hosting_plan_edit']);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create([
        'category_id' => $category->id,
        'contabo_product_id' => 'V45',
    ]);

    $response = $this->actingAs($user)
        ->put(route('admin.hosting-plans.update', $plan), [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'tagline' => $plan->tagline,
            'is_popular' => $plan->is_popular ? 1 : 0,
            'status' => $plan->status->value,
            'sort_order' => $plan->sort_order,
            'category_id' => $category->id,
            'contabo_product_id' => '',
        ]);

    $response->assertRedirect(route('admin.hosting-plans.index'));

    $plan->refresh();
    expect($plan->contabo_product_id)->toBeNull();
});
