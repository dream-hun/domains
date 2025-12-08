<?php

declare(strict_types=1);

use App\Enums\Hosting\CategoryStatus;
use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Enums\Hosting\HostingPlanStatus;
use App\Livewire\Hosting\Configuration;
use App\Models\Currency;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\User;
use App\Services\HostingSubscriptionService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Livewire;

beforeEach(function (): void {
    Cart::clear();

    Currency::query()->firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]
    );

    session(['selected_currency' => 'USD']);
});

afterEach(function (): void {
    Cart::clear();
    session()->forget(['selected_currency']);
});

it('allows VPS hosting category to not require domain', function (): void {
    $vpsCategory = HostingCategory::factory()->create([
        'name' => 'VPS Hosting',
        'slug' => 'vps-hosting',
        'status' => CategoryStatus::Active->value,
    ]);

    expect($vpsCategory->requiresDomain())->toBeFalse();
});

it('requires domain for shared hosting category', function (): void {
    $sharedCategory = HostingCategory::factory()->create([
        'name' => 'Shared Hosting',
        'slug' => 'shared-hosting',
        'status' => CategoryStatus::Active->value,
    ]);

    expect($sharedCategory->requiresDomain())->toBeTrue();
});

it('can select "none" domain option for VPS plan', function (): void {
    $user = User::factory()->create();

    $vpsCategory = HostingCategory::factory()->create([
        'name' => 'VPS Hosting',
        'slug' => 'vps-hosting',
        'status' => CategoryStatus::Active->value,
    ]);

    $vpsPlan = HostingPlan::factory()->create([
        'category_id' => $vpsCategory->id,
        'status' => HostingPlanStatus::Active->value,
    ]);

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $vpsPlan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 5000,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);

    Livewire::actingAs($user)
        ->test(Configuration::class, ['plan' => $vpsPlan])
        ->set('domainOption', 'none')
        ->call('confirmDomainSelection')
        ->assertSet('domainConfirmed', true)
        ->assertDispatched('notify');
});

it('can add VPS to cart without domain', function (): void {
    $user = User::factory()->create();

    $vpsCategory = HostingCategory::factory()->create([
        'name' => 'VPS Hosting',
        'slug' => 'vps-hosting',
        'status' => CategoryStatus::Active->value,
    ]);

    $vpsPlan = HostingPlan::factory()->create([
        'category_id' => $vpsCategory->id,
        'status' => HostingPlanStatus::Active->value,
    ]);

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $vpsPlan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 5000,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);

    Livewire::actingAs($user)
        ->test(Configuration::class, ['plan' => $vpsPlan])
        ->set('domainOption', 'none')
        ->call('confirmDomainSelection')
        ->call('addToCart')
        ->assertRedirect(route('cart.index'));

    $hostingItem = Cart::getContent()->first(fn ($item): bool => $item->attributes->get('type') === 'hosting');

    expect($hostingItem)->not->toBeNull()
        ->and($hostingItem->attributes->get('linked_domain'))->toBeNull()
        ->and($hostingItem->attributes->get('domain_required'))->toBeFalse()
        ->and($hostingItem->attributes->get('metadata')['hosting_plan_id'])->toBe($vpsPlan->id)
        ->and($hostingItem->attributes->get('metadata')['hosting_plan_price_id'])->toBe($price->id);
});

it('creates subscription for VPS without domain', function (): void {
    $vpsCategory = HostingCategory::factory()->create([
        'name' => 'VPS Hosting',
        'slug' => 'vps-hosting',
        'status' => CategoryStatus::Active->value,
    ]);

    $vpsPlan = HostingPlan::factory()->create([
        'category_id' => $vpsCategory->id,
        'status' => HostingPlanStatus::Active->value,
    ]);

    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $vpsPlan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 5000,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);

    $order = Order::factory()->paid()->create([
        'currency' => 'USD',
        'type' => 'hosting',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => $vpsPlan->name,
        'domain_type' => 'hosting',
        'price' => 50.00,
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'quantity' => 1,
        'years' => 1,
        'total_amount' => 50.00,
        'metadata' => [
            'hosting_plan_id' => $vpsPlan->id,
            'hosting_plan_price_id' => $planPrice->id,
            'billing_cycle' => 'monthly',
            'linked_domain' => null,
            'domain_required' => false,
        ],
    ]);

    app(HostingSubscriptionService::class)->createSubscriptionsFromOrder($order->fresh('orderItems'));

    $subscription = Subscription::query()->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->domain)->toBeNull()
        ->and($subscription->hosting_plan_id)->toBe($vpsPlan->id)
        ->and($subscription->hosting_plan_price_id)->toBe($planPrice->id)
        ->and($subscription->billing_cycle)->toBe('monthly')
        ->and($subscription->status)->toBe('active');
});

it('prevents shared hosting from using "none" domain option', function (): void {
    $user = User::factory()->create();

    $sharedCategory = HostingCategory::factory()->create([
        'name' => 'Shared Hosting',
        'slug' => 'shared-hosting',
        'status' => CategoryStatus::Active->value,
    ]);

    $sharedPlan = HostingPlan::factory()->create([
        'category_id' => $sharedCategory->id,
        'status' => HostingPlanStatus::Active->value,
    ]);

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $sharedPlan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 1000,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);

    // Shared hosting requires domain, so addToCart should fail without domain confirmation
    Livewire::actingAs($user)
        ->test(Configuration::class, ['plan' => $sharedPlan])
        ->set('domainOption', 'none')
        ->call('addToCart')
        ->assertHasErrors('base');
});
