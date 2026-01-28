<?php

declare(strict_types=1);

use App\Helpers\CurrencyHelper;
use App\Livewire\CartComponent;
use App\Models\Currency;
use App\Models\User;
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
    session()->forget(['cart', 'cart_subtotal', 'cart_total', 'coupon', 'selected_currency']);
});

it('stores converted totals in session when proceeding to payment', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'RWF']);

    Cart::add([
        'id' => 'test-renewal',
        'name' => 'example.net (Renewal)',
        'price' => 160.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'renewal',
            'currency' => 'USD',
            'domain_id' => null,
            'years' => 1,
            'added_at' => now()->timestamp,
        ],
    ]);

    $expectedTotal = CurrencyHelper::convert(160.0, 'USD', 'RWF');

    $component = Livewire::test(CartComponent::class)
        ->set('currency', 'RWF')
        ->call('updateCartTotal');

    expect($component->get('subtotalAmount'))->toBeGreaterThan(0)
        ->and($component->get('totalAmount'))->toBeGreaterThan(0);

    $component->call('proceedToPayment')
        ->assertRedirect(route('checkout.index'));

    expect(session('cart_total'))->toEqual($expectedTotal)
        ->and(session('cart_subtotal'))->toEqual($expectedTotal)
        ->and(session('selected_currency'))->toBe('RWF');
});
