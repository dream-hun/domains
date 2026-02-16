<?php

declare(strict_types=1);

use App\Enums\Hosting\BillingCycle;
use App\Models\Currency;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createPriceAdmin(array $permissions): User
{
    $user = User::factory()->create();
    $role = Role::query()->create(['title' => 'PriceAdmin']);
    $role->permissions()->attach(
        Permission::query()->whereIn('title', $permissions)->pluck('id')
    );
    $user->roles()->attach($role);

    return $user;
}

function makePlanPrice(array $overrides = []): HostingPlanPrice
{
    return HostingPlanPrice::factory()->create($overrides);
}

// --- Index ---

test('index requires authentication', function (): void {
    $this->get(route('admin.hosting-plan-prices.index'))
        ->assertRedirect(route('login'));
});

test('index is accessible to any authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.hosting-plan-prices.index'))
        ->assertSuccessful();
});

test('index loads with currency column and filter', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_access']);
    $price = makePlanPrice();
    $price->load('currency');

    $response = $this->actingAs($user)
        ->get(route('admin.hosting-plan-prices.index'));

    $response->assertSuccessful();
    $response->assertViewHas('prices');
    $response->assertViewHas('currencies');
    $response->assertViewHas('selectedCurrencyId');
});

test('index filters by currency', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_access']);
    $currencyA = Currency::factory()->create(['is_active' => true]);
    $currencyB = Currency::factory()->create(['is_active' => true]);

    $plan = HostingPlan::factory()->create();
    $priceA = HostingPlanPrice::factory()->create(['currency_id' => $currencyA->id, 'hosting_plan_id' => $plan->id]);
    $priceB = HostingPlanPrice::factory()->create(['currency_id' => $currencyB->id, 'hosting_plan_id' => $plan->id]);

    $response = $this->actingAs($user)
        ->get(route('admin.hosting-plan-prices.index', ['currency_id' => $currencyA->id]));

    $response->assertSuccessful();
    $prices = $response->viewData('prices');
    expect($prices->pluck('id')->toArray())->toContain($priceA->id)
        ->and($prices->pluck('id')->toArray())->not->toContain($priceB->id);
});

// --- Create ---

test('create form shows currency dropdown, is_current, and effective_date', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_create']);
    $currency = Currency::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)
        ->get(route('admin.hosting-plan-prices.create'));

    $response->assertSuccessful();
    $response->assertViewHas('currencies');
    $response->assertSee('currency_id');
    $response->assertSee('is_current');
    $response->assertSee('effective_date');
    $response->assertSee($currency->code);
});

// --- Store ---

test('store creates record with currency_id, is_current, effective_date', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_create']);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create(['category_id' => $category->id]);
    $currency = Currency::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)
        ->post(route('admin.hosting-plan-prices.store'), [
            'hosting_category_id' => $category->id,
            'hosting_plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'billing_cycle' => BillingCycle::Monthly->value,
            'regular_price' => 9.99,
            'renewal_price' => 12.99,
            'status' => 'active',
            'is_current' => true,
            'effective_date' => '2026-02-16',
        ]);

    $response->assertRedirect(route('admin.hosting-plan-prices.index'));
    $response->assertSessionHas('success');

    $price = HostingPlanPrice::query()->latest('id')->first();
    expect($price->currency_id)->toBe($currency->id)
        ->and($price->is_current)->toBeTrue()
        ->and($price->effective_date->format('Y-m-d'))->toBe('2026-02-16')
        ->and((float) $price->regular_price)->toBe(9.99)
        ->and((float) $price->renewal_price)->toBe(12.99);
});

test('store validates currency_id required', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_create']);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create(['category_id' => $category->id]);

    $response = $this->actingAs($user)
        ->post(route('admin.hosting-plan-prices.store'), [
            'hosting_category_id' => $category->id,
            'hosting_plan_id' => $plan->id,
            'billing_cycle' => BillingCycle::Monthly->value,
            'regular_price' => 9.99,
            'renewal_price' => 12.99,
            'status' => 'active',
            'is_current' => true,
            'effective_date' => '2026-02-16',
        ]);

    $response->assertSessionHasErrors('currency_id');
});

test('store validates is_current and effective_date required', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_create']);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create(['category_id' => $category->id]);
    $currency = Currency::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)
        ->post(route('admin.hosting-plan-prices.store'), [
            'hosting_category_id' => $category->id,
            'hosting_plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'billing_cycle' => BillingCycle::Monthly->value,
            'regular_price' => 9.99,
            'renewal_price' => 12.99,
            'status' => 'active',
        ]);

    $response->assertSessionHasErrors(['is_current', 'effective_date']);
});

// --- Update ---

test('update works with new fields', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_edit']);
    $price = makePlanPrice();
    $newCurrency = Currency::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)
        ->patch(route('admin.hosting-plan-prices.update', $price->uuid), [
            'hosting_category_id' => $price->plan->category_id,
            'hosting_plan_id' => $price->hosting_plan_id,
            'currency_id' => $newCurrency->id,
            'billing_cycle' => $price->billing_cycle,
            'regular_price' => $price->regular_price,
            'renewal_price' => $price->renewal_price,
            'status' => 'active',
            'is_current' => false,
            'effective_date' => '2026-03-01',
        ]);

    $response->assertRedirect(route('admin.hosting-plan-prices.index'));

    $price->refresh();
    expect($price->currency_id)->toBe($newCurrency->id)
        ->and($price->is_current)->toBeFalse()
        ->and($price->effective_date->format('Y-m-d'))->toBe('2026-03-01');
});

test('update requires reason on price change', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_edit']);
    $currency = Currency::factory()->create(['is_active' => true]);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create(['category_id' => $category->id]);
    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'regular_price' => 10.00,
        'renewal_price' => 15.00,
    ]);

    // Refresh so the model has database-cast values matching what route binding returns
    $price->refresh();

    $response = $this->actingAs($user)
        ->from(route('admin.hosting-plan-prices.edit', $price->uuid))
        ->patch(route('admin.hosting-plan-prices.update', $price->uuid), [
            'hosting_category_id' => $category->id,
            'hosting_plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'billing_cycle' => $price->billing_cycle,
            'regular_price' => 99.99,
            'renewal_price' => 15.00,
            'status' => 'active',
            'is_current' => true,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertSessionHasErrors('reason');
});

test('update with price change and reason succeeds', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_edit']);
    $currency = Currency::factory()->create(['is_active' => true]);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create(['category_id' => $category->id]);
    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'regular_price' => 10.00,
        'renewal_price' => 15.00,
    ]);

    $response = $this->actingAs($user)
        ->patch(route('admin.hosting-plan-prices.update', $price->uuid), [
            'hosting_category_id' => $category->id,
            'hosting_plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'billing_cycle' => $price->billing_cycle,
            'regular_price' => 20.00,
            'renewal_price' => 15.00,
            'status' => 'active',
            'is_current' => true,
            'effective_date' => now()->format('Y-m-d'),
            'reason' => 'Market adjustment for pricing',
        ]);

    $response->assertRedirect(route('admin.hosting-plan-prices.index'));

    $price->refresh();
    expect((float) $price->regular_price)->toBe(20.00);
});

// --- Delete ---

test('delete works', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_delete']);
    $price = makePlanPrice();

    $response = $this->actingAs($user)
        ->delete(route('admin.hosting-plan-prices.destroy', $price->uuid));

    $response->assertRedirect(route('admin.hosting-plan-prices.index'));
    $response->assertSessionHas('success');

    expect(HostingPlanPrice::query()->find($price->id))->toBeNull();
});

// --- Model Tests ---

test('model current() scope filters correctly', function (): void {
    $currentPrice = makePlanPrice(['is_current' => true]);
    $oldPrice = makePlanPrice(['is_current' => false]);

    $currentPrices = HostingPlanPrice::query()->current()->pluck('id')->toArray();

    expect($currentPrices)->toContain($currentPrice->id)
        ->and($currentPrices)->not->toContain($oldPrice->id);
});

test('model getBaseCurrency returns actual currency code', function (): void {
    $currency = Currency::factory()->create();
    $price = makePlanPrice(['currency_id' => $currency->id]);
    $price->load('currency');

    expect($price->getBaseCurrency())->toBe($currency->code);
});

test('model getBaseCurrency falls back to USD when no currency', function (): void {
    $price = new HostingPlanPrice;

    expect($price->getBaseCurrency())->toBe('USD');
});

// --- Price History Observer ---

test('price history observer fires on price change', function (): void {
    $user = createPriceAdmin(['hosting_plan_price_edit']);
    $currency = Currency::factory()->create(['is_active' => true]);
    $category = HostingCategory::factory()->create();
    $plan = HostingPlan::factory()->create(['category_id' => $category->id]);
    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'regular_price' => 10.00,
        'renewal_price' => 15.00,
    ]);

    $this->actingAs($user)
        ->patch(route('admin.hosting-plan-prices.update', $price->uuid), [
            'hosting_category_id' => $category->id,
            'hosting_plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'billing_cycle' => $price->billing_cycle,
            'regular_price' => 25.00,
            'renewal_price' => 15.00,
            'status' => 'active',
            'is_current' => true,
            'effective_date' => now()->format('Y-m-d'),
            'reason' => 'Testing history recording',
        ]);

    $histories = $price->hostingPlanPriceHistories()->get();
    expect($histories)->toHaveCount(1);
    expect($histories->first()->changes)->toHaveKey('regular_price');
});
