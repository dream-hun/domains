<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

it('creates a payment with attempt_number, failure_details, and last_attempted_at', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 1,
        'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
        'last_attempted_at' => now(),
    ]);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->attempt_number)->toBe(1)
        ->and($payment->failure_details)->toBeNull()
        ->and($payment->last_attempted_at)->not->toBeNull();
});

it('defaults attempt_number to 1 when not specified', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'stripe_payment_intent_id' => 'pi_test_default',
    ]);

    $payment->refresh();

    expect($payment->attempt_number)->toBe(1);
});

it('stores failure_details as json', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    $failureDetails = [
        'code' => 'card_declined',
        'message' => 'Your card was declined.',
        'decline_code' => 'insufficient_funds',
    ];

    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'failed',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 2,
        'failure_details' => $failureDetails,
        'last_attempted_at' => now(),
        'stripe_payment_intent_id' => 'pi_test_failed',
    ]);

    $payment->refresh();

    expect($payment->failure_details)->toBe($failureDetails)
        ->and($payment->attempt_number)->toBe(2);
});

it('tracks multiple payment attempts for an order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    // First attempt - failed
    Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'failed',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 1,
        'failure_details' => ['code' => 'card_declined'],
        'last_attempted_at' => now()->subMinutes(5),
        'stripe_payment_intent_id' => 'pi_attempt_1',
    ]);

    // Second attempt - succeeded
    Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'succeeded',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 2,
        'last_attempted_at' => now(),
        'paid_at' => now(),
        'stripe_payment_intent_id' => 'pi_attempt_2',
    ]);

    $latestPayment = Payment::query()->latestForOrder($order)->first();

    expect($latestPayment->attempt_number)->toBe(2)
        ->and($latestPayment->status)->toBe('succeeded')
        ->and($order->payments()->count())->toBe(2);
});
