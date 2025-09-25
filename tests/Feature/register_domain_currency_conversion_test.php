<?php

declare(strict_types=1);

use App\Services\CurrencyService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use App\Models\User;

it('converts cart amounts to user preferred currency and displays them', function () {
    // Create a user and authenticate
    $user = User::factory()->create();
    actingAs($user);

    // Prepare a fake cart item
    $cartItem = (object) [
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 10.00,
        'quantity' => 2,
        'attributes' => (object) ['currency' => 'USD'],
    ];

    // Mock Cart facade
    Cart::shouldReceive('getContent')->andReturn(collect([$cartItem]));
    Cart::shouldReceive('getTotal')->andReturn(20.00);

    // Bind a fake CurrencyService that converts by doubling amounts and formats with EUR symbol
    $fakeCurrencyService = new class {
        public function getUserCurrency()
        {
            return (object) ['code' => 'EUR'];
        }

        public function convert(float $amount, string $from, string $target): float
        {
            // for test purposes: return double the amount
            return round($amount * 2, 2);
        }

        public function format(float $amount, string $currencyCode): string
        {
            return '€' . number_format($amount, 2);
        }
    };

    $this->app->instance(CurrencyService::class, $fakeCurrencyService);

    // Request the register page
    $response = $this->get(route('domains.register'));

    $response->assertStatus(200);

    // The fake convert doubles unit price: unit 10 => converted unit 20, quantity 2 => line total 40
    $response->assertSee('€40.00');

    // Total should also be formatted and displayed
    $response->assertSee('€40.00');
});

