<?php

declare(strict_types=1);

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Enums\Hosting\HostingPlanStatus;
use App\Livewire\HostingUpsell;
use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
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

it('adds hosting with metadata linked to a domain item', function (): void {
    $plan = HostingPlan::factory()->create([
        'status' => HostingPlanStatus::Active->value,
    ]);

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 1500,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);

    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 12.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'domain' => 'example.com',
            'domain_name' => 'example.com',
            'currency' => 'USD',
            'added_at' => now()->timestamp,
        ],
    ]);

    Livewire::test(HostingUpsell::class)
        ->set('selectedDomain', 'example.com')
        ->call('addHosting', $plan->id, 'monthly');

    $hostingItem = Cart::getContent()->first(fn ($item): bool => $item->attributes->get('type') === 'hosting');

    expect($hostingItem)->not->toBeNull()
        ->and($hostingItem->attributes->get('linked_domain'))->toBe('example.com')
        ->and($hostingItem->attributes->get('metadata')['hosting_plan_id'])->toBe($plan->id)
        ->and($hostingItem->attributes->get('metadata')['hosting_plan_price_id'])->toBe($price->id);
});

it('creates a subscription from hosting order items', function (): void {
    $plan = HostingPlan::factory()->create([
        'status' => HostingPlanStatus::Active->value,
    ]);

    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 2500,
        'status' => HostingPlanPriceStatus::Active->value,
    ]);

    $order = Order::factory()->paid()->create([
        'currency' => 'USD',
    ]);

    $attachedDomain = 'bundle-example.com';

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => $plan->name,
        'domain_type' => 'hosting',
        'price' => 25.00,
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'quantity' => 1,
        'years' => 1,
        'total_amount' => 25.00,
        'metadata' => [
            'hosting_plan_id' => $plan->id,
            'hosting_plan_price_id' => $planPrice->id,
            'billing_cycle' => 'monthly',
            'linked_domain' => $attachedDomain,
        ],
    ]);

    app(HostingSubscriptionService::class)->createSubscriptionsFromOrder($order->fresh('orderItems'));

    $subscription = Subscription::query()->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->domain)->toBe($attachedDomain)
        ->and($subscription->hosting_plan_id)->toBe($plan->id)
        ->and($subscription->hosting_plan_price_id)->toBe($planPrice->id)
        ->and($subscription->billing_cycle)->toBe('monthly')
        ->and($subscription->status)->toBe('active');
});
