<?php

declare(strict_types=1);

use App\Livewire\CheckoutProcess;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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

    Currency::query()->firstOrCreate(
        ['code' => 'RWF'],
        [
            'name' => 'Rwandan Franc',
            'symbol' => 'FRW',
            'exchange_rate' => 1450.0,
            'is_base' => false,
            'is_active' => true,
        ]
    );

    session(['selected_currency' => 'RWF']);
});

afterEach(function (): void {
    Cart::clear();
    session()->forget(['cart', 'cart_subtotal', 'cart_total', 'checkout']);
});

it('keeps rwf totals intact when proceeding to payment', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Cart::add([
        'id' => 'renewal-rwf',
        'name' => 'example.rw (Renewal)',
        'price' => 16000.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'renewal',
            'currency' => 'RWF',
            'domain_id' => null,
            'years' => 1,
        ],
    ]);

    Livewire::test(CheckoutProcess::class)
        ->set('currency', 'RWF')
        ->call('refreshCart')
        ->call('calculateTotals')
        ->call('proceedToPayment')
        ->assertRedirect(route('checkout.index'));

    expect(session('cart_total'))->toEqual(16000.0)
        ->and(session('checkout.total'))->toEqual(16000.0)
        ->and(session('checkout.currency'))->toBe('RWF');
});
