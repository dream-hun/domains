<?php

declare(strict_types=1);

use App\Livewire\Checkout\CheckoutWizard;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use App\Services\CurrencyService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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

    Currency::query()->firstOrCreate(
        ['code' => 'EUR'],
        [
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.92,
            'is_base' => false,
            'is_active' => true,
        ]
    );

    Currency::query()->firstOrCreate(
        ['code' => 'RWF'],
        [
            'name' => 'Rwandan Franc',
            'symbol' => 'FRW',
            'exchange_rate' => 1350.0,
            'is_base' => false,
            'is_active' => true,
        ]
    );
});

afterEach(function (): void {
    Cart::clear();
    session()->forget(['cart', 'cart_subtotal', 'cart_total', 'coupon', 'selected_currency', 'checkout_state']);
});

it('completes checkout with items in different currencies converted to order currency', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    session(['selected_currency' => 'EUR']);

    // Add items in different currencies
    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    Cart::add([
        'id' => 'domain-2',
        'name' => 'example.net',
        'price' => 135000.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'RWF',
            'domain_name' => 'example.net',
        ],
    ]);

    $component = Livewire::test(CheckoutWizard::class)
        ->set('selectedRegistrantId', $contact->id)
        ->set('selectedAdminId', $contact->id)
        ->set('selectedTechId', $contact->id)
        ->set('selectedBillingId', $contact->id)
        ->set('selectedPaymentMethod', 'stripe')
        ->call('completeOrder');

    // Verify order was created
    $order = Order::query()->where('user_id', $user->id)->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->currency)->toBe('EUR');

    // Item 1: 100 USD = 92 EUR
    // Item 2: 135000 RWF = 100 USD = 92 EUR
    // Total: 184 EUR
    expect($order->subtotal)->toBe(184.0);
    expect($order->total_amount)->toBe(184.0);

    // Verify order items are in EUR
    $orderItems = $order->orderItems()->get();
    expect($orderItems)->toHaveCount(2);
    expect($orderItems->pluck('currency')->unique()->toArray())->toBe(['EUR']);

    // Verify original currencies are stored in metadata
    $item1 = $orderItems->firstWhere('domain_name', 'example.com');
    $item2 = $orderItems->firstWhere('domain_name', 'example.net');

    expect($item1->metadata['original_currency'])->toBe('USD');
    expect($item1->metadata['original_price'])->toBe(100.0);
    expect($item2->metadata['original_currency'])->toBe('RWF');
    expect($item2->metadata['original_price'])->toBe(135000.0);
});

it('calculates checkout totals correctly with mixed item types', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    Cart::add([
        'id' => 'hosting-1',
        'name' => 'Hosting Plan',
        'price' => 120.0,
        'quantity' => 12,
        'attributes' => [
            'type' => 'hosting',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'monthly_unit_price' => 10.0,
        ],
    ]);

    $component = Livewire::test(CheckoutWizard::class);

    // Domain: 100 * 0.92 = 92 EUR
    // Hosting: (10 * 0.92) * 12 = 110.4 EUR
    // Total: 202.4 EUR
    expect($component->get('orderSubtotal'))->toBe(202.4);
    expect($component->get('orderTotal'))->toBe(202.4);
});

it('updates checkout totals when currency changes', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $component = Livewire::test(CheckoutWizard::class);

    // Initially in USD
    expect($component->get('userCurrencyCode'))->toBe('USD');
    expect($component->get('orderSubtotal'))->toBe(100.0);

    // Change to EUR
    session(['selected_currency' => 'EUR']);
    $component->set('userCurrencyCode', 'EUR')
        ->call('mount', app(CurrencyService::class));

    expect($component->get('orderSubtotal'))->toBe(92.0);
});

it('handles checkout with hosting items correctly', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'hosting-1',
        'name' => 'Hosting Plan',
        'price' => 120.0,
        'quantity' => 12,
        'attributes' => [
            'type' => 'hosting',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'monthly_unit_price' => 10.0,
        ],
    ]);

    $component = Livewire::test(CheckoutWizard::class)
        ->set('selectedBillingId', $contact->id)
        ->set('selectedPaymentMethod', 'stripe')
        ->call('completeOrder');

    $order = Order::query()->where('user_id', $user->id)->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->currency)->toBe('EUR');

    // Monthly price: 10 USD = 9.2 EUR
    // Total: 9.2 * 12 = 110.4 EUR
    expect($order->subtotal)->toBe(110.4);
    expect($order->total_amount)->toBe(110.4);

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('EUR');
    expect($orderItem->price)->toBe(9.2); // Monthly price
    expect($orderItem->total_amount)->toBe(110.4);
});

it('handles checkout with subscription renewal items correctly', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'sub-renewal',
        'name' => 'Subscription Renewal',
        'price' => 120.0,
        'quantity' => 12,
        'attributes' => [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'display_unit_price' => 120.0,
        ],
    ]);

    $component = Livewire::test(CheckoutWizard::class)
        ->set('selectedBillingId', $contact->id)
        ->set('selectedPaymentMethod', 'stripe')
        ->call('completeOrder');

    $order = Order::query()->where('user_id', $user->id)->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->currency)->toBe('EUR');

    // Annual price: 120 USD = 110.4 EUR
    // Years: 12 / 12 = 1
    // Total: 110.4 * 1 = 110.4 EUR
    expect($order->subtotal)->toBe(110.4);
    expect($order->total_amount)->toBe(110.4);

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('EUR');
    expect($orderItem->total_amount)->toBe(110.4);
});

it('preserves original currency information in order items for audit', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $component = Livewire::test(CheckoutWizard::class)
        ->set('selectedRegistrantId', $contact->id)
        ->set('selectedAdminId', $contact->id)
        ->set('selectedTechId', $contact->id)
        ->set('selectedBillingId', $contact->id)
        ->set('selectedPaymentMethod', 'stripe')
        ->call('completeOrder');

    $order = Order::query()->where('user_id', $user->id)->latest()->first();
    $orderItem = $order->orderItems()->first();

    // Verify audit information is stored
    expect($orderItem->metadata)->toHaveKey('original_currency');
    expect($orderItem->metadata)->toHaveKey('original_price');
    expect($orderItem->metadata['original_currency'])->toBe('USD');
    expect($orderItem->metadata['original_price'])->toBe(100.0);

    // Verify converted values are stored
    expect($orderItem->currency)->toBe('EUR');
    expect($orderItem->price)->toBe(92.0);
    expect($orderItem->exchange_rate)->toBe(0.92);
});

it('handles checkout with discount in different currency', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    // Apply discount (already in EUR from cart component)
    $component = Livewire::test(CheckoutWizard::class)
        ->set('selectedRegistrantId', $contact->id)
        ->set('selectedAdminId', $contact->id)
        ->set('selectedTechId', $contact->id)
        ->set('selectedBillingId', $contact->id)
        ->set('selectedPaymentMethod', 'stripe')
        ->set('discountAmount', 9.2) // 10 EUR discount
        ->call('completeOrder');

    $order = Order::query()->where('user_id', $user->id)->latest()->first();

    // Subtotal: 100 * 0.92 = 92 EUR
    // Discount: 9.2 EUR
    // Total: 92 - 9.2 = 82.8 EUR
    expect($order->subtotal)->toBe(92.0);
    expect($order->discount_amount)->toBe(9.2);
    expect($order->total_amount)->toBe(82.8);
});
