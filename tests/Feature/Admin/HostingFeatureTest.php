<?php

declare(strict_types=1);

use App\Actions\HostingFeature\CreateHostingFeatureAction;
use App\Actions\HostingFeature\ListHostingFeatureAction;
use App\Actions\HostingFeature\UpdateHostingFeatureAction;
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
        Permission::query()->firstOrCreate(['id' => 100, 'title' => 'hosting_feature_access'])->id,
        Permission::query()->firstOrCreate(['id' => 101, 'title' => 'hosting_feature_create'])->id,
        Permission::query()->firstOrCreate(['id' => 102, 'title' => 'hosting_feature_edit'])->id,
        Permission::query()->firstOrCreate(['id' => 103, 'title' => 'hosting_feature_delete'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]); // Detach default role 2, attach admin role 1
});

it('displays hosting features on index page', function (): void {
    HostingFeature::factory()->create(['name' => 'SSD Storage', 'slug' => 'ssd-storage']);
    HostingFeature::factory()->create(['name' => 'Email Accounts', 'slug' => 'email-accounts']);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-features');

    $response->assertSuccessful();
    $response->assertSee('SSD Storage');
    $response->assertSee('Email Accounts');
});

it('allows admin to view create form', function (): void {
    FeatureCategory::factory()->create(['name' => 'Storage']);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-features/create');

    $response->assertSuccessful();
    $response->assertSee('Create Hosting Feature');
});

it('allows admin to create a new hosting feature', function (): void {
    $category = FeatureCategory::factory()->create();

    $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'New Feature',
        'slug' => 'new-feature',
        'description' => 'A new feature',
        'icon' => 'bi bi-star',
        'category' => 'resources',
        'feature_category_id' => $category->id,
        'value_type' => 'numeric',
        'unit' => 'GB',
        'sort_order' => 1,
        'is_highlighted' => true,
    ])->assertRedirect('/admin/hosting-features');

    $this->assertDatabaseHas('hosting_features', [
        'name' => 'New Feature',
        'slug' => 'new-feature',
        'description' => 'A new feature',
        'icon' => 'bi bi-star',
        'category' => 'resources',
        'feature_category_id' => $category->id,
        'value_type' => 'numeric',
        'unit' => 'GB',
        'sort_order' => 1,
        'is_highlighted' => true,
    ]);
});

it('auto-generates slug from name when slug is not provided', function (): void {
    $action = new CreateHostingFeatureAction();

    $feature = $action->handle([
        'name' => 'My New Feature',
        'category' => 'general',
        'value_type' => 'text',
    ]);

    expect($feature->slug)->toBe('my-new-feature');
});

it('ensures slug uniqueness when creating feature', function (): void {
    HostingFeature::factory()->create(['slug' => 'existing-slug']);

    $action = new CreateHostingFeatureAction();

    $feature = $action->handle([
        'name' => 'New Feature',
        'slug' => 'existing-slug',
        'category' => 'general',
        'value_type' => 'text',
    ]);

    expect($feature->slug)->toBe('existing-slug-1');
});

it('validates required fields when creating hosting feature', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-features', []);

    $response->assertSessionHasErrors(['name', 'category', 'value_type']);
});

it('validates value_type is one of allowed values', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'Test Feature',
        'value_type' => 'invalid-type',
    ]);

    $response->assertSessionHasErrors('value_type');
});

it('validates feature_category_id exists', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'Test Feature',
        'feature_category_id' => 99999,
    ]);

    $response->assertSessionHasErrors('feature_category_id');
});

it('validates slug uniqueness when creating', function (): void {
    HostingFeature::factory()->create(['slug' => 'existing-slug']);

    $response = $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'Test Feature',
        'slug' => 'existing-slug',
    ]);

    $response->assertSessionHasErrors('slug');
});

it('allows admin to view edit form', function (): void {
    $feature = HostingFeature::factory()->create();
    FeatureCategory::factory()->create();

    $response = $this->actingAs($this->admin)->get(sprintf('/admin/hosting-features/%s/edit', $feature->uuid));

    $response->assertSuccessful();
    $response->assertSee($feature->name);
});

it('allows admin to update a hosting feature', function (): void {
    $category = FeatureCategory::factory()->create();
    $feature = HostingFeature::factory()->create(['name' => 'Old Name', 'slug' => 'old-slug']);

    $this->actingAs($this->admin)->put('/admin/hosting-features/'.$feature->uuid, [
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'description' => 'Updated description',
        'icon' => 'bi bi-updated',
        'feature_category_id' => $category->id,
        'value_type' => 'boolean',
        'unit' => 'accounts',
        'sort_order' => 5,
        'is_highlighted' => false,
    ])->assertRedirect('/admin/hosting-features');

    $this->assertDatabaseHas('hosting_features', [
        'id' => $feature->id,
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'description' => 'Updated description',
        'icon' => 'bi bi-updated',
        'feature_category_id' => $category->id,
        'value_type' => 'boolean',
        'unit' => 'accounts',
        'sort_order' => 5,
        'is_highlighted' => false,
    ]);
});

it('auto-generates slug from name when updating if slug is empty', function (): void {
    $feature = HostingFeature::factory()->create(['name' => 'Old Name', 'slug' => 'old-slug']);

    $action = new UpdateHostingFeatureAction();

    $updated = $action->handle($feature, [
        'name' => 'New Feature Name',
    ]);

    expect($updated->slug)->toBe('new-feature-name');
});

it('ensures slug uniqueness when updating feature', function (): void {
    HostingFeature::factory()->create(['slug' => 'existing-slug']);
    $feature = HostingFeature::factory()->create(['slug' => 'my-slug']);

    $action = new UpdateHostingFeatureAction();

    $updated = $action->handle($feature, [
        'name' => 'New Name',
        'slug' => 'existing-slug',
    ]);

    expect($updated->slug)->toBe('existing-slug-1');
});

it('validates slug uniqueness when updating', function (): void {
    HostingFeature::factory()->create(['slug' => 'existing-slug']);
    $feature = HostingFeature::factory()->create(['slug' => 'my-slug']);

    $response = $this->actingAs($this->admin)->put('/admin/hosting-features/'.$feature->uuid, [
        'name' => 'Updated Name',
        'slug' => 'existing-slug',
    ]);

    $response->assertSessionHasErrors('slug');
});

it('allows deletion of hosting feature', function (): void {
    $feature = HostingFeature::factory()->create();

    $this->actingAs($this->admin)->delete('/admin/hosting-features/'.$feature->uuid)
        ->assertRedirect('/admin/hosting-features');

    $this->assertDatabaseMissing('hosting_features', ['id' => $feature->id]);
});

it('lists hosting features ordered by sort order and name', function (): void {
    HostingFeature::factory()->create(['name' => 'Z Feature', 'sort_order' => 2]);
    HostingFeature::factory()->create(['name' => 'A Feature', 'sort_order' => 1]);
    HostingFeature::factory()->create(['name' => 'B Feature', 'sort_order' => 1]);

    $action = new ListHostingFeatureAction();
    $features = $action->handle();

    expect($features->pluck('name')->toArray())->toBe(['A Feature', 'B Feature', 'Z Feature']);
});

it('includes feature category when listing features', function (): void {
    $category = FeatureCategory::factory()->create(['name' => 'Storage']);
    $feature = HostingFeature::factory()->create(['feature_category_id' => $category->id]);

    $action = new ListHostingFeatureAction();
    $features = $action->handle();

    $foundFeature = $features->firstWhere('id', $feature->id);
    expect($foundFeature->featureCategory)->not->toBeNull();
    expect($foundFeature->featureCategory->name)->toBe('Storage');
});

it('requires authentication to access hosting features', function (): void {
    $this->get('/admin/hosting-features')->assertRedirect('/login');
});

it('requires permission to access hosting features', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/hosting-features')->assertForbidden();
});

it('requires permission to create hosting feature', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/hosting-features/create')->assertForbidden();
});

it('requires permission to edit hosting feature', function (): void {
    $feature = HostingFeature::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(sprintf('/admin/hosting-features/%s/edit', $feature->uuid))->assertForbidden();
});

it('requires permission to delete hosting feature', function (): void {
    $feature = HostingFeature::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->delete('/admin/hosting-features/'.$feature->uuid)->assertForbidden();
});

it('displays success message after creating feature', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'New Feature',
        'category' => 'general',
        'value_type' => 'text',
    ]);

    $response->assertRedirect('/admin/hosting-features');
    $response->assertSessionHas('success', 'Hosting feature created successfully.');
});

it('displays success message after updating feature', function (): void {
    $feature = HostingFeature::factory()->create();

    $response = $this->actingAs($this->admin)->put('/admin/hosting-features/'.$feature->uuid, [
        'name' => 'Updated Name',
    ]);

    $response->assertRedirect('/admin/hosting-features');
    $response->assertSessionHas('success', 'Hosting feature updated successfully.');
});

it('displays success message after deleting feature', function (): void {
    $feature = HostingFeature::factory()->create();

    $response = $this->actingAs($this->admin)->delete('/admin/hosting-features/'.$feature->uuid);

    $response->assertRedirect('/admin/hosting-features');
    $response->assertSessionHas('success', 'Hosting feature deleted successfully.');
});

it('handles create action with uuid generation', function (): void {
    $action = new CreateHostingFeatureAction();

    $feature = $action->handle([
        'name' => 'Test Feature',
        'category' => 'general',
        'value_type' => 'text',
    ]);

    expect($feature->uuid)->not->toBeNull();
    expect($feature->uuid)->toBeString();
});

it('handles update action without changing slug when name unchanged', function (): void {
    $feature = HostingFeature::factory()->create(['name' => 'Test Feature', 'slug' => 'test-feature']);

    $action = new UpdateHostingFeatureAction();

    $updated = $action->handle($feature, [
        'description' => 'Updated description',
    ]);

    expect($updated->slug)->toBe('test-feature');
    expect($updated->name)->toBe('Test Feature');
});

it('validates sort order is a non-negative integer', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'Test Feature',
        'sort_order' => -1,
    ]);

    $response->assertSessionHasErrors('sort_order');
});

it('allows empty description when creating feature', function (): void {
    $this->actingAs($this->admin)->post('/admin/hosting-features', [
        'name' => 'Test Feature',
        'category' => 'general',
        'value_type' => 'text',
        'description' => '',
    ])->assertRedirect('/admin/hosting-features');

    $this->assertDatabaseHas('hosting_features', [
        'name' => 'Test Feature',
        'description' => null,
    ]);
});

it('sets default values for sort_order and is_highlighted when creating', function (): void {
    $action = new CreateHostingFeatureAction();

    $feature = $action->handle([
        'name' => 'Test Feature',
        'category' => 'general',
        'value_type' => 'text',
    ]);

    expect($feature->sort_order)->toBe(0);
    expect($feature->is_highlighted)->toBeFalse();
});

it('allows all value types when creating feature', function (): void {
    $valueTypes = ['boolean', 'numeric', 'text', 'unlimited'];

    foreach ($valueTypes as $valueType) {
        $this->actingAs($this->admin)->post('/admin/hosting-features', [
            'name' => 'Test Feature '.$valueType,
            'category' => 'general',
            'value_type' => $valueType,
        ])->assertRedirect('/admin/hosting-features');

        $this->assertDatabaseHas('hosting_features', [
            'name' => 'Test Feature '.$valueType,
            'value_type' => $valueType,
        ]);
    }
});
