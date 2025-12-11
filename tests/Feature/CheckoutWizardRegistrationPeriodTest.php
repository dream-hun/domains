<?php

declare(strict_types=1);

use App\Enums\Hosting\BillingCycle;
use App\Livewire\Checkout\CheckoutWizard;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Livewire;

beforeEach(function (): void {
    Cart::clear();

    Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2], ['title' => 'Customer']);

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

    $this->user = User::factory()->create();
});

afterEach(function (): void {
    Cart::clear();
});

it('displays correct period for subscription renewal with monthly billing cycle', function (): void {
    Cart::add([
        'id' => 'subscription-renewal-1',
        'name' => 'example.com - Starter (Renewal)',
        'price' => 10.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'subscription_renewal',
            'billing_cycle' => BillingCycle::Monthly->value,
            'subscription_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('1 month renewal');
});

it('displays correct period for subscription renewal with quarterly billing cycle', function (): void {
    Cart::add([
        'id' => 'subscription-renewal-1',
        'name' => 'example.com - Starter (Renewal)',
        'price' => 30.00,
        'quantity' => 3,
        'attributes' => [
            'type' => 'subscription_renewal',
            'billing_cycle' => BillingCycle::Quarterly->value,
            'subscription_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('3 months renewal');
});

it('displays correct period for subscription renewal with annually billing cycle', function (): void {
    Cart::add([
        'id' => 'subscription-renewal-1',
        'name' => 'example.com - Starter (Renewal)',
        'price' => 100.00,
        'quantity' => 12,
        'attributes' => [
            'type' => 'subscription_renewal',
            'billing_cycle' => BillingCycle::Annually->value,
            'subscription_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('1 year renewal');
});

it('displays correct period for domain renewal', function (): void {
    Cart::add([
        'id' => 'renewal-1',
        'name' => 'example.com (Renewal)',
        'price' => 15.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'renewal',
            'domain_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('1 year renewal');
});

it('displays correct period for multi-year domain renewal', function (): void {
    Cart::add([
        'id' => 'renewal-1',
        'name' => 'example.com (Renewal)',
        'price' => 15.00,
        'quantity' => 3,
        'attributes' => [
            'type' => 'renewal',
            'domain_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('3 years renewal');
});

it('displays correct period for domain registration', function (): void {
    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 12.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('1 year of registration');
});

it('falls back to duration_months for subscription renewal when billing_cycle is missing', function (): void {
    Cart::add([
        'id' => 'subscription-renewal-1',
        'name' => 'example.com - Starter (Renewal)',
        'price' => 10.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'subscription_renewal',
            'duration_months' => 1,
            'subscription_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('1 month renewal');
});

it('falls back to quantity for subscription renewal when both billing_cycle and duration_months are missing', function (): void {
    Cart::add([
        'id' => 'subscription-renewal-1',
        'name' => 'example.com - Starter (Renewal)',
        'price' => 10.00,
        'quantity' => 12,
        'attributes' => [
            'type' => 'subscription_renewal',
            'subscription_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('1 year renewal');
});

it('handles subscription renewal with biennially billing cycle', function (): void {
    Cart::add([
        'id' => 'subscription-renewal-1',
        'name' => 'example.com - Starter (Renewal)',
        'price' => 200.00,
        'quantity' => 24,
        'attributes' => [
            'type' => 'subscription_renewal',
            'billing_cycle' => BillingCycle::Biennially->value,
            'subscription_id' => 1,
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(CheckoutWizard::class)
        ->assertSee('2 years renewal');
});
