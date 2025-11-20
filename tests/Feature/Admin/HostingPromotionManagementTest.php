<?php

declare(strict_types=1);

use App\Models\HostingPlan;
use App\Models\HostingPromotion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Role::query()->firstOrCreate(['id' => 1, 'title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2, 'title' => 'User']);

    $this->admin = User::factory()->create();

    $permissionIds = collect([
        ['id' => 94, 'title' => 'hosting_promotion_create'],
        ['id' => 95, 'title' => 'hosting_promotion_edit'],
        ['id' => 97, 'title' => 'hosting_promotion_delete'],
        ['id' => 98, 'title' => 'hosting_promotion_access'],
    ])->map(fn (array $permission): int => Permission::query()->firstOrCreate($permission)->id);

    Role::query()->findOrFail(1)->permissions()->sync($permissionIds->all());
    $this->admin->roles()->sync([1]);

    $this->plan = HostingPlan::factory()->create();
});

it('displays hosting promotions on index page', function (): void {
    HostingPromotion::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'discount_percentage' => 25,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/hosting-promotions');

    $response->assertOk();
    $response->assertSee('Hosting Promotions');
});

it('allows admin to view create promotion page', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/hosting-promotions/create');

    $response->assertOk();
    $response->assertSee('Create Hosting Promotion');
});

it('creates a new hosting promotion', function (): void {
    $startsAt = Carbon::now()->addDay()->format('Y-m-d\TH:i');
    $endsAt = Carbon::now()->addDays(10)->format('Y-m-d\TH:i');

    $response = $this->actingAs($this->admin)->post('/admin/hosting-promotions', [
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'discount_percentage' => 15,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    $response->assertRedirect('/admin/hosting-promotions');
    $response->assertSessionHas('success', 'Hosting promotion created successfully.');

    $this->assertDatabaseHas('hosting_promotions', [
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'discount_percentage' => 15,
    ]);
});

it('validates required fields when creating promotion', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/hosting-promotions', []);

    $response->assertSessionHasErrors([
        'hosting_plan_id',
        'billing_cycle',
        'discount_percentage',
        'starts_at',
        'ends_at',
    ]);
});

it('updates an existing hosting promotion', function (): void {
    $promotion = HostingPromotion::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'discount_percentage' => 10,
    ]);

    $response = $this->actingAs($this->admin)->put('/admin/hosting-promotions/'.$promotion->uuid, [
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'annually',
        'discount_percentage' => 40,
        'starts_at' => Carbon::now()->subDay()->format('Y-m-d\TH:i'),
        'ends_at' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect('/admin/hosting-promotions');
    $response->assertSessionHas('success', 'Hosting promotion updated successfully.');

    $this->assertDatabaseHas('hosting_promotions', [
        'uuid' => $promotion->uuid,
        'billing_cycle' => 'annually',
        'discount_percentage' => 40,
    ]);
});

it('deletes a hosting promotion', function (): void {
    $promotion = HostingPromotion::factory()->create([
        'hosting_plan_id' => $this->plan->id,
    ]);

    $response = $this->actingAs($this->admin)->delete('/admin/hosting-promotions/'.$promotion->uuid);

    $response->assertRedirect('/admin/hosting-promotions');
    $response->assertSessionHas('success', 'Hosting promotion deleted successfully.');

    $this->assertDatabaseMissing('hosting_promotions', [
        'uuid' => $promotion->uuid,
    ]);
});

it('requires authentication to access hosting promotions', function (): void {
    $this->get('/admin/hosting-promotions')->assertRedirect('/login');
});
