<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('billing show displays pay now button for failed order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->failed()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('billing.show', $order));

    $response->assertStatus(200);
    $response->assertSee('Pay Now');
});

test('billing show does not display pay now button for paid order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('billing.show', $order));

    $response->assertStatus(200);
    $response->assertDontSee('Pay Now');
});

test('user can access retry payment page for own failed order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->failed()->create(['user_id' => $user->id]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($user)->get(route('billing.retry-payment', $order));

    $response->assertStatus(200);
    $response->assertSee('Select Payment Method');
    $response->assertSee($order->order_number);
});

test('user cannot access retry payment page for another users order', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->failed()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get(route('billing.retry-payment', $order));

    $response->assertStatus(403);
});

test('user cannot access retry payment page for paid order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('billing.retry-payment', $order));

    $response->assertRedirect(route('billing.show', $order));
});

test('retry payment with kpay sets session and redirects', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->failed()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('billing.retry-payment.process', $order), [
        'payment_method' => 'kpay',
    ]);

    $response->assertRedirect(route('payment.kpay.show'));

    expect(session('kpay_order_number'))->toBe($order->order_number);

    $order->refresh();
    expect($order->payment_status)->toBe('pending');
    expect($order->payment_method)->toBe('kpay');
});

test('retry payment with stripe updates order and attempts payment', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->failed()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('billing.retry-payment.process', $order), [
        'payment_method' => 'stripe',
    ]);

    $order->refresh();
    expect($order->payment_status)->toBe('pending');
    expect($order->payment_method)->toBe('stripe');

    // Stripe is not configured in test env, so it should redirect back with error
    $response->assertRedirect(route('billing.retry-payment', $order));
});

test('retry payment requires valid payment method', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->failed()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('billing.retry-payment.process', $order), [
        'payment_method' => 'invalid',
    ]);

    $response->assertSessionHasErrors('payment_method');
});

test('retry payment cannot process paid order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('billing.retry-payment.process', $order), [
        'payment_method' => 'stripe',
    ]);

    $response->assertRedirect(route('billing.show', $order));
});

test('canRetryPayment returns true for pending orders', function (): void {
    $order = Order::factory()->pending()->make();

    expect($order->canRetryPayment())->toBeTrue();
});

test('canRetryPayment returns true for failed orders', function (): void {
    $order = Order::factory()->failed()->make();

    expect($order->canRetryPayment())->toBeTrue();
});

test('canRetryPayment returns false for paid orders', function (): void {
    $order = Order::factory()->paid()->make();

    expect($order->canRetryPayment())->toBeFalse();
});

test('canRetryPayment returns false for cancelled orders', function (): void {
    $order = Order::factory()->make(['payment_status' => 'cancelled']);

    expect($order->canRetryPayment())->toBeFalse();
});
