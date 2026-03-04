<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getPreferredCurrencyFromPayments returns null when user has no payments', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($address->getPreferredCurrencyFromPayments())->toBeNull();
});

test('getPreferredCurrencyFromPayments returns null when user_id is not set', function (): void {
    $address = new Address();

    expect($address->getPreferredCurrencyFromPayments())->toBeNull();
});

test('getPreferredCurrencyFromPayments returns currency from most recent successful payment', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    $order1 = Order::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);
    $order2 = Order::factory()->create(['user_id' => $user->id, 'currency' => 'RWF']);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order1->id,
        'status' => 'succeeded',
        'currency' => 'USD',
        'paid_at' => now()->subDays(2),
    ]);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order2->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now()->subDay(),
    ]);

    expect($address->getPreferredCurrencyFromPayments())->toBe('RWF');
});

test('getPreferredCurrencyFromPayments ignores failed payments', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    $order = Order::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'failed',
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now(),
    ]);

    expect($address->getPreferredCurrencyFromPayments())->toBe('RWF');
});

test('setPreferredCurrencyIfNotSet sets currency from payments when preferred_currency is null', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => null,
    ]);

    $order = Order::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now(),
    ]);

    $address->setPreferredCurrencyIfNotSet();

    expect($address->preferred_currency)->toBe('RWF');
});

test('setPreferredCurrencyIfNotSet does not overwrite existing preferred_currency', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'USD',
    ]);

    $order = Order::factory()->create(['user_id' => $user->id, 'currency' => 'RWF']);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now(),
    ]);

    $address->setPreferredCurrencyIfNotSet();

    expect($address->preferred_currency)->toBe('USD');
});

test('setPreferredCurrencyIfNotSet is called automatically on save', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now(),
    ]);

    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => null,
    ]);

    $address->save();

    $address->refresh();

    expect($address->preferred_currency)->toBe('RWF');
});
