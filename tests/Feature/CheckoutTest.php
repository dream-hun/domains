<?php

declare(strict_types=1);

use App\Enums\Coupons\CouponType;
use App\Livewire\CartComponent;
use App\Livewire\CheckoutProcess;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    // Create necessary roles for user creation
    Role::query()->create(['id' => 1, 'title' => 'Admin']);
    Role::query()->create(['id' => 2, 'title' => 'User']);

    // Seed currencies for testing
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);
});

it('displays checkout page with empty cart', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('checkout.index'));

    $response->assertStatus(302);
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'Your cart is empty.');
});

it('displays checkout page with cart items', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 10.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    $response = $this->actingAs($user)->get(route('checkout.index'));

    $response->assertStatus(200);
    $response->assertSeeLivewire('checkout-process');
});

it('applies coupon successfully', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 100.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    // Create a test coupon
    $coupon = Coupon::query()->create([
        'uuid' => Str::uuid(),
        'code' => 'TEST10',
        'type' => CouponType::Percentage,
        'value' => 10,
        'valid_from' => now()->subDay(),
        'valid_to' => now()->addMonth(),
        'max_uses' => 100,
        'uses' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(CartComponent::class)
        ->set('couponCode', 'TEST10')
        ->call('applyCoupon')
        ->assertDispatched('notify', function ($eventName, $payload) {
            return $payload[0]['type'] === 'success' && str_contains($payload[0]['message'], 'applied');
        });

    $couponData = session('coupon');
    expect($couponData['code'])->toBe('TEST10');
    expect((float) $couponData['discount_amount'])->toBe(10.0); // 10% of $100
});

it('rejects invalid coupon', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 100.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    Livewire::actingAs($user)
        ->test(CartComponent::class)
        ->set('couponCode', 'INVALID')
        ->call('applyCoupon')
        ->assertDispatched('notify', function ($eventName, $payload) {
            return $payload[0]['type'] === 'error';
        });

    expect(session()->has('coupon'))->toBeFalse();
});

it('proceeds to payment with valid data', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 100.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    // Create a contact for the user to select
    $contact = App\Models\Contact::factory()->create([
        'user_id' => $user->id,
        'is_primary' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutProcess::class)
        ->set('paymentMethod', 'stripe')
        ->set('selectedContactId', $contact->id)
        ->call('proceedToPayment')
        ->assertRedirect(route('payment.index'));

    // Verify checkout data is stored in session
    expect(session('checkout'))->not->toBeNull();
});

it('fails to proceed with empty cart', function (): void {
    $user = User::factory()->create();

    // Cart is empty by default

    Livewire::actingAs($user)
        ->test(CheckoutProcess::class)
        ->call('proceedToPayment');
    // CheckoutProcess::proceedToPayment returns null if empty, and sets errorMessage
    // It does NOT return 400 status because it's Livewire.

    // We can check if errorMessage is set
    Livewire::actingAs($user)
        ->test(CheckoutProcess::class)
        ->call('proceedToPayment')
        ->assertSet('errorMessage', 'Your cart is empty.');
});
