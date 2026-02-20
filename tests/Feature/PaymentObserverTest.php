<?php

declare(strict_types=1);

use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('successful payment updates address preferred_currency', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => null,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now(),
    ]);

    $address->refresh();

    expect($address->preferred_currency)->toBe('RWF');
});

test('failed payment does not update address preferred_currency', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'USD',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'failed',
        'currency' => 'RWF',
    ]);

    $address->refresh();

    expect($address->preferred_currency)->toBe('USD');
});

test('pending payment does not update address preferred_currency', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'USD',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'currency' => 'RWF',
    ]);

    $address->refresh();

    expect($address->preferred_currency)->toBe('USD');
});

test('successful payment updates address preferred_currency when payment status changes to succeeded', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'USD',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'currency' => 'RWF',
    ]);

    $address->refresh();
    expect($address->preferred_currency)->toBe('USD');

    $payment->status = 'succeeded';
    $payment->paid_at = now();
    $payment->save();

    $address->refresh();
    expect($address->preferred_currency)->toBe('RWF');
});

test('payment for user without address does not cause error', function (): void {
    $user = User::factory()->create();
    // User has no address

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => 'RWF',
        'paid_at' => now(),
    ]);

    // Should not throw an error even though user has no address
    expect(Payment::query()->where('id', $payment->id)->exists())->toBeTrue();
});

test('payment without currency does not update address', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'USD',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'succeeded',
        'currency' => '',
        'paid_at' => now(),
    ]);

    $address->refresh();

    expect($address->preferred_currency)->toBe('USD');
});
