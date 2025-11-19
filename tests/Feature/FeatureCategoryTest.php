<?php

declare(strict_types=1);

use App\Actions\FeatureCategory\CreateFeatureCategoryAction;
use App\Actions\FeatureCategory\ListFeatureCategoryAction;
use App\Actions\FeatureCategory\UpdateFeatureCategoryAction;
use App\Models\FeatureCategory;
use App\Models\HostingFeature;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create both necessary roles (user model automatically attaches role id 2)
    Role::query()->firstOrCreate(['id' => 1, 'title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2, 'title' => 'User']);

    // Create admin user
    $this->admin = User::factory()->create();

    // Attach admin role and permissions
    $adminRole = Role::query()->find(1);
    $permissionIds = [
        Permission::query()->firstOrCreate(['id' => 77, 'title' => 'feature_category_access'])->id,
        Permission::query()->firstOrCreate(['id' => 73, 'title' => 'feature_category_create'])->id,
        Permission::query()->firstOrCreate(['id' => 74, 'title' => 'feature_category_edit'])->id,
        Permission::query()->firstOrCreate(['id' => 76, 'title' => 'feature_category_delete'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]); // Detach default role 2, attach admin role 1
});

it('displays feature categories on index page', function (): void {
    FeatureCategory::factory()->create(['name' => 'Features', 'slug' => 'features']);
    FeatureCategory::factory()->create(['name' => 'Tech Specs', 'slug' => 'tech-specs']);

    $response = $this->actingAs($this->admin)->get('/admin/feature-categories');

    $response->assertSuccessful();
    $response->assertSee('Features');
    $response->assertSee('Tech Specs');
});

it('allows admin to view create form', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/feature-categories/create');

    $response->assertSuccessful();
    $response->assertSee('Create Feature Category');
});

it('allows admin to create a new feature category', function (): void {
    $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'New Category',
        'slug' => 'new-category',
        'description' => 'A new category',
        'icon' => 'bi bi-star',
        'sort_order' => 1,
        'status' => 'active',
    ])->assertRedirect('/admin/feature-categories');

    $this->assertDatabaseHas('feature_categories', [
        'name' => 'New Category',
        'slug' => 'new-category',
        'description' => 'A new category',
        'icon' => 'bi bi-star',
        'sort_order' => 1,
    ]);
});

it('auto-generates slug from name when slug is not provided', function (): void {
    $action = new CreateFeatureCategoryAction();

    $category = $action->handle([
        'name' => 'My New Category',
        'status' => 'active',
    ]);

    expect($category->slug)->toBe('my-new-category');
});

it('ensures slug uniqueness when creating category', function (): void {
    FeatureCategory::factory()->create(['slug' => 'existing-slug']);

    $action = new CreateFeatureCategoryAction();

    $category = $action->handle([
        'name' => 'New Category',
        'slug' => 'existing-slug',
        'status' => 'active',
    ]);

    expect($category->slug)->toBe('existing-slug-1');
});

it('validates required fields when creating feature category', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/feature-categories', []);

    $response->assertSessionHasErrors(['name', 'status']);
});

it('validates status is either active or inactive', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'Test Category',
        'status' => 'invalid-status',
    ]);

    $response->assertSessionHasErrors('status');
});

it('validates slug uniqueness when creating', function (): void {
    FeatureCategory::factory()->create(['slug' => 'existing-slug']);

    $response = $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'Test Category',
        'slug' => 'existing-slug',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('slug');
});

it('allows admin to view edit form', function (): void {
    $category = FeatureCategory::factory()->create();

    $response = $this->actingAs($this->admin)->get(sprintf('/admin/feature-categories/%s/edit', $category->slug));

    $response->assertSuccessful();
    $response->assertSee($category->name);
});

it('allows admin to update a feature category', function (): void {
    $category = FeatureCategory::factory()->create(['name' => 'Old Name', 'slug' => 'old-slug']);

    $this->actingAs($this->admin)->put('/admin/feature-categories/'.$category->slug, [
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'description' => 'Updated description',
        'icon' => 'bi bi-updated',
        'sort_order' => 5,
        'status' => 'inactive',
    ])->assertRedirect('/admin/feature-categories');

    $this->assertDatabaseHas('feature_categories', [
        'id' => $category->id,
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'description' => 'Updated description',
        'icon' => 'bi bi-updated',
        'sort_order' => 5,
    ]);
});

it('auto-generates slug from name when updating if slug is empty', function (): void {
    $category = FeatureCategory::factory()->create(['name' => 'Old Name', 'slug' => 'old-slug']);

    $action = new UpdateFeatureCategoryAction();

    $updated = $action->handle($category, [
        'name' => 'New Category Name',
        'status' => 'active',
    ]);

    expect($updated->slug)->toBe('new-category-name');
});

it('ensures slug uniqueness when updating category', function (): void {
    FeatureCategory::factory()->create(['slug' => 'existing-slug']);
    $category = FeatureCategory::factory()->create(['slug' => 'my-slug']);

    $action = new UpdateFeatureCategoryAction();

    $updated = $action->handle($category, [
        'name' => 'New Name',
        'slug' => 'existing-slug',
        'status' => 'active',
    ]);

    expect($updated->slug)->toBe('existing-slug-1');
});

it('validates slug uniqueness when updating', function (): void {
    FeatureCategory::factory()->create(['slug' => 'existing-slug']);
    $category = FeatureCategory::factory()->create(['slug' => 'my-slug']);

    $response = $this->actingAs($this->admin)->put('/admin/feature-categories/'.$category->slug, [
        'name' => 'Updated Name',
        'slug' => 'existing-slug',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('slug');
});

it('allows deletion of feature category without associated features', function (): void {
    $category = FeatureCategory::factory()->create();

    $this->actingAs($this->admin)->delete('/admin/feature-categories/'.$category->slug)
        ->assertRedirect('/admin/feature-categories');

    $this->assertDatabaseMissing('feature_categories', ['id' => $category->id]);
});

it('prevents deletion of feature category with associated hosting features', function (): void {
    $category = FeatureCategory::factory()->create();
    HostingFeature::factory()->create(['feature_category_id' => $category->id]);

    $response = $this->actingAs($this->admin)->delete('/admin/feature-categories/'.$category->slug);

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('feature_categories', ['id' => $category->id]);
});

it('lists feature categories ordered by sort order and name', function (): void {
    FeatureCategory::factory()->create(['name' => 'Z Category', 'sort_order' => 2]);
    FeatureCategory::factory()->create(['name' => 'A Category', 'sort_order' => 1]);
    FeatureCategory::factory()->create(['name' => 'B Category', 'sort_order' => 1]);

    $action = new ListFeatureCategoryAction();
    $categories = $action->handle();

    expect($categories->pluck('name')->toArray())->toBe(['A Category', 'B Category', 'Z Category']);
});

it('includes hosting features count when listing categories', function (): void {
    $category = FeatureCategory::factory()->create();
    HostingFeature::factory()->count(3)->create(['feature_category_id' => $category->id]);

    $action = new ListFeatureCategoryAction();
    $categories = $action->handle();

    $foundCategory = $categories->firstWhere('id', $category->id);
    expect($foundCategory->hosting_features_count)->toBe(3);
});

it('requires authentication to access feature categories', function (): void {
    $this->get('/admin/feature-categories')->assertRedirect('/login');
});

it('requires permission to access feature categories', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/feature-categories')->assertForbidden();
});

it('requires permission to create feature category', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/feature-categories/create')->assertForbidden();
});

it('requires permission to edit feature category', function (): void {
    $category = FeatureCategory::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(sprintf('/admin/feature-categories/%s/edit', $category->slug))->assertForbidden();
});

it('requires permission to delete feature category', function (): void {
    $category = FeatureCategory::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->delete('/admin/feature-categories/'.$category->slug)->assertForbidden();
});

it('displays success message after creating category', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'New Category',
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/feature-categories');
    $response->assertSessionHas('success', 'Feature category created successfully.');
});

it('displays success message after updating category', function (): void {
    $category = FeatureCategory::factory()->create();

    $response = $this->actingAs($this->admin)->put('/admin/feature-categories/'.$category->slug, [
        'name' => 'Updated Name',
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/feature-categories');
    $response->assertSessionHas('success', 'Feature category updated successfully.');
});

it('displays success message after deleting category', function (): void {
    $category = FeatureCategory::factory()->create();

    $response = $this->actingAs($this->admin)->delete('/admin/feature-categories/'.$category->slug);

    $response->assertRedirect('/admin/feature-categories');
    $response->assertSessionHas('success', 'Feature category deleted successfully.');
});

it('handles create action with uuid generation', function (): void {
    $action = new CreateFeatureCategoryAction();

    $category = $action->handle([
        'name' => 'Test Category',
        'status' => 'active',
    ]);

    expect($category->uuid)->not->toBeNull();
    expect($category->uuid)->toBeString();
});

it('handles update action without changing slug when name unchanged', function (): void {
    $category = FeatureCategory::factory()->create(['name' => 'Test Category', 'slug' => 'test-category']);

    $action = new UpdateFeatureCategoryAction();

    $updated = $action->handle($category, [
        'description' => 'Updated description',
        'status' => 'active',
    ]);

    expect($updated->slug)->toBe('test-category');
    expect($updated->name)->toBe('Test Category');
});

it('validates sort order is a non-negative integer', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'Test Category',
        'status' => 'active',
        'sort_order' => -1,
    ]);

    $response->assertSessionHasErrors('sort_order');
});

it('allows empty description when creating category', function (): void {
    $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'Test Category',
        'status' => 'active',
        'description' => '',
    ])->assertRedirect('/admin/feature-categories');

    $this->assertDatabaseHas('feature_categories', [
        'name' => 'Test Category',
        'description' => null,
    ]);
});

it('allows empty icon when creating category', function (): void {
    $this->actingAs($this->admin)->post('/admin/feature-categories', [
        'name' => 'Test Category',
        'status' => 'active',
        'icon' => '',
    ])->assertRedirect('/admin/feature-categories');

    $this->assertDatabaseHas('feature_categories', [
        'name' => 'Test Category',
        'icon' => null,
    ]);
});
