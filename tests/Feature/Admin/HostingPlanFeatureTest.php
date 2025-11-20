<?php

declare(strict_types=1);

use App\Actions\HostingPlanFeature\DeleteHostingPlanFeatureAction;
use App\Actions\HostingPlanFeature\ListHostingPlanFeatureAction;
use App\Actions\HostingPlanFeature\StoreHostingPlanFeatureAction;
use App\Actions\HostingPlanFeature\UpdateHostingPlanFeatureAction;
use App\Models\HostingCategory;
use App\Models\HostingFeature;
use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
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
        Permission::query()->firstOrCreate(['id' => 200, 'title' => 'hosting_plan_feature_access'])->id,
        Permission::query()->firstOrCreate(['id' => 201, 'title' => 'hosting_plan_feature_create'])->id,
        Permission::query()->firstOrCreate(['id' => 202, 'title' => 'hosting_plan_feature_edit'])->id,
        Permission::query()->firstOrCreate(['id' => 203, 'title' => 'hosting_plan_feature_delete'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]); // Detach default role 2, attach admin role 1

    // Create a hosting plan and feature for testing
    $this->hostingPlan = HostingPlan::factory()->create(['name' => 'Basic Plan']);
    $this->hostingPlan->load('category');
    $this->hostingCategory = $this->hostingPlan->category;
    $this->hostingFeature = HostingFeature::factory()->create(['name' => 'Storage Space']);
});

it('displays hosting plan features on index page', function (): void {
    HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '100 GB',
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-plan-features');

    $response->assertSuccessful();
    $response->assertSee('Basic Plan');
    $response->assertSee('Storage Space');
    $response->assertSee('100 GB');
});

it('allows admin to view create form', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/hosting-plan-features/create');

    $response->assertSuccessful();
    $response->assertSee('Create Hosting Plan Feature');
});

it('allows admin to create a new hosting plan feature', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '50 GB',
        'is_unlimited' => false,
        'is_included' => true,
        'custom_text' => '50 GB of storage',
        'sort_order' => 1,
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');
    $response->assertSessionHas('success', 'Hosting plan feature created successfully.');

    $this->assertDatabaseHas('hosting_plan_features', [
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '50 GB',
        'is_unlimited' => false,
        'is_included' => true,
        'custom_text' => '50 GB of storage',
        'sort_order' => 1,
    ]);
});

it('validates required fields when creating hosting plan feature', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', []);

    $response->assertSessionHasErrors(['hosting_category_id', 'hosting_plan_id', 'hosting_feature_id']);
});

it('validates hosting_plan_id exists', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => 99999,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response->assertSessionHasErrors('hosting_plan_id');
});

it('validates hosting_feature_id exists', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => 99999,
    ]);

    $response->assertSessionHasErrors('hosting_feature_id');
});

it('prevents duplicate hosting_plan_id and hosting_feature_id combination', function (): void {
    HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response->assertSessionHasErrors('hosting_feature_id');
});

it('validates sort_order is a non-negative integer', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'sort_order' => -1,
    ]);

    $response->assertSessionHasErrors('sort_order');
});

it('allows admin to view edit form', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response = $this->actingAs($this->admin)->get(sprintf('/admin/hosting-plan-features/%s/edit', $planFeature->id));

    $response->assertSuccessful();
    $response->assertSee('Edit Hosting Plan Feature');
});

it('allows admin to update a hosting plan feature', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '50 GB',
        'is_unlimited' => false,
        'is_included' => true,
    ]);

    $anotherPlan = HostingPlan::factory()->create(['name' => 'Pro Plan']);
    $anotherPlan->load('category');

    $response = $this->actingAs($this->admin)->put('/admin/hosting-plan-features/'.$planFeature->id, [
        'hosting_category_id' => $anotherPlan->category_id,
        'hosting_plan_id' => $anotherPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '200 GB',
        'is_unlimited' => true,
        'is_included' => true,
        'custom_text' => 'Unlimited storage',
        'sort_order' => 5,
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');
    $response->assertSessionHas('success', 'Hosting plan feature updated successfully.');

    $this->assertDatabaseHas('hosting_plan_features', [
        'id' => $planFeature->id,
        'hosting_plan_id' => $anotherPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '200 GB',
        'is_unlimited' => true,
        'is_included' => true,
        'custom_text' => 'Unlimited storage',
        'sort_order' => 5,
    ]);
});

it('prevents duplicate hosting_plan_id and hosting_feature_id combination when updating', function (): void {
    $existingPlanFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $anotherFeature = HostingFeature::factory()->create();
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $anotherFeature->id,
    ]);

    $response = $this->actingAs($this->admin)->put('/admin/hosting-plan-features/'.$planFeature->id, [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response->assertSessionHasErrors('hosting_feature_id');
});

it('allows deletion of hosting plan feature', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response = $this->actingAs($this->admin)->delete('/admin/hosting-plan-features/'.$planFeature->id);

    $response->assertRedirect('/admin/hosting-plan-features');
    $response->assertSessionHas('success', 'Hosting plan feature deleted successfully.');

    $this->assertDatabaseMissing('hosting_plan_features', ['id' => $planFeature->id]);
});

it('lists hosting plan features ordered by sort_order and id', function (): void {
    $planFeature1 = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'sort_order' => 2,
    ]);
    $planFeature2 = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'sort_order' => 1,
    ]);
    $planFeature3 = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'sort_order' => 1,
    ]);

    $action = new ListHostingPlanFeatureAction();
    $planFeatures = $action->handle();

    $ids = $planFeatures->pluck('id')->toArray();
    expect($ids)->toContain($planFeature2->id, $planFeature3->id, $planFeature1->id);
    expect(array_search($planFeature1->id, $ids))->toBeGreaterThan(array_search($planFeature2->id, $ids));
    expect(array_search($planFeature1->id, $ids))->toBeGreaterThan(array_search($planFeature3->id, $ids));
});

it('includes hosting plan and hosting feature relationships when listing', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $action = new ListHostingPlanFeatureAction();
    $planFeatures = $action->handle();

    $foundPlanFeature = $planFeatures->firstWhere('id', $planFeature->id);
    expect($foundPlanFeature->hostingPlan)->not->toBeNull();
    expect($foundPlanFeature->hostingPlan->name)->toBe('Basic Plan');
    expect($foundPlanFeature->hostingPlan->category)->not->toBeNull();
    expect($foundPlanFeature->hostingFeature)->not->toBeNull();
    expect($foundPlanFeature->hostingFeature->name)->toBe('Storage Space');
});

it('handles store action with uuid generation', function (): void {
    $action = new StoreHostingPlanFeatureAction();

    $planFeature = $action->handle([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '100 GB',
    ]);

    expect($planFeature->uuid)->not->toBeNull();
    expect($planFeature->uuid)->toBeString();
});

it('handles update action', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '50 GB',
        'is_unlimited' => false,
    ]);

    $action = new UpdateHostingPlanFeatureAction();

    $updated = $action->handle($planFeature, [
        'feature_value' => '200 GB',
        'is_unlimited' => true,
    ]);

    $refreshed = $updated->fresh();
    expect($refreshed->feature_value)->toBe('200 GB');
    expect($refreshed->is_unlimited)->toBeTrue();
});

it('handles delete action', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $action = new DeleteHostingPlanFeatureAction();
    $action->handle($planFeature);

    $this->assertDatabaseMissing('hosting_plan_features', ['id' => $planFeature->id]);
});

it('creates plan feature with default values when not provided', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');

    $this->assertDatabaseHas('hosting_plan_features', [
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'is_unlimited' => false,
        'is_included' => true,
        'sort_order' => 0,
    ]);
});

it('handles boolean fields correctly when creating', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'is_unlimited' => '1',
        'is_included' => '1',
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');

    $this->assertDatabaseHas('hosting_plan_features', [
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'is_unlimited' => true,
        'is_included' => true,
    ]);
});

it('handles boolean fields correctly when updating', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'is_unlimited' => false,
        'is_included' => true,
    ]);

    $response = $this->actingAs($this->admin)->put('/admin/hosting-plan-features/'.$planFeature->id, [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'is_unlimited' => '1',
        'is_included' => '0',
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');

    $this->assertDatabaseHas('hosting_plan_features', [
        'id' => $planFeature->id,
        'is_unlimited' => true,
        'is_included' => false,
    ]);
});

it('requires authentication to access hosting plan features', function (): void {
    $this->get('/admin/hosting-plan-features')->assertRedirect('/login');
});

it('requires permission to access hosting plan features', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/hosting-plan-features')->assertForbidden();
});

it('requires permission to create hosting plan feature', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/hosting-plan-features/create')->assertForbidden();
});

it('requires permission to edit hosting plan feature', function (): void {
    $planFeature = HostingPlanFeature::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(sprintf('/admin/hosting-plan-features/%s/edit', $planFeature->id))->assertForbidden();
});

it('requires permission to delete hosting plan feature', function (): void {
    $planFeature = HostingPlanFeature::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->delete('/admin/hosting-plan-features/'.$planFeature->id)->assertForbidden();
});

it('displays success message after creating plan feature', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');
    $response->assertSessionHas('success', 'Hosting plan feature created successfully.');
});

it('displays success message after updating plan feature', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response = $this->actingAs($this->admin)->put('/admin/hosting-plan-features/'.$planFeature->id, [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => 'Updated value',
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');
    $response->assertSessionHas('success', 'Hosting plan feature updated successfully.');
});

it('displays success message after deleting plan feature', function (): void {
    $planFeature = HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response = $this->actingAs($this->admin)->delete('/admin/hosting-plan-features/'.$planFeature->id);

    $response->assertRedirect('/admin/hosting-plan-features');
    $response->assertSessionHas('success', 'Hosting plan feature deleted successfully.');
});

it('allows nullable fields when creating', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => null,
        'custom_text' => null,
    ]);

    $response->assertRedirect('/admin/hosting-plan-features');

    $this->assertDatabaseHas('hosting_plan_features', [
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => null,
        'custom_text' => null,
    ]);
});

it('filters hosting plan features by category and plan', function (): void {
    $anotherCategory = HostingCategory::factory()->create(['name' => 'Pro']);
    $anotherPlan = HostingPlan::factory()->create([
        'name' => 'Pro Plan',
        'category_id' => $anotherCategory->id,
    ]);
    $anotherFeature = HostingFeature::factory()->create(['name' => 'Bandwidth']);

    HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $this->hostingPlan->id,
        'hosting_feature_id' => $this->hostingFeature->id,
        'feature_value' => '100 GB',
    ]);

    HostingPlanFeature::factory()->create([
        'hosting_plan_id' => $anotherPlan->id,
        'hosting_feature_id' => $anotherFeature->id,
        'feature_value' => '200 GB',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/hosting-plan-features?hosting_category_id='.$this->hostingCategory->id)
        ->assertSee('Storage Space')
        ->assertDontSee('Bandwidth');

    $this->actingAs($this->admin)
        ->get('/admin/hosting-plan-features?hosting_plan_id='.$anotherPlan->id)
        ->assertSee('Bandwidth')
        ->assertDontSee('Storage Space');
});

it('validates hosting plan belongs to selected category', function (): void {
    $differentCategory = HostingCategory::factory()->create();
    $planFromDifferentCategory = HostingPlan::factory()->create([
        'category_id' => $differentCategory->id,
    ]);

    $response = $this->actingAs($this->admin)->post('/admin/hosting-plan-features', [
        'hosting_category_id' => $this->hostingCategory->id,
        'hosting_plan_id' => $planFromDifferentCategory->id,
        'hosting_feature_id' => $this->hostingFeature->id,
    ]);

    $response->assertSessionHasErrors('hosting_plan_id');
});
