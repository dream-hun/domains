<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Config::set('services.payment.stripe.secret_key', 'sk_test_fake_key');
    Config::set('services.payment.stripe.publishable_key', 'pk_test_fake_key');

    Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2], ['title' => 'User']);
});

afterEach(function (): void {
    Mockery::close();
});

it('marks payment attempt as succeeded when payment completes', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->for($user)->create([
        'payment_status' => 'pending',
        'status' => 'pending',
        'stripe_session_id' => 'cs_test_success',
    ]);

    $paymentAttempt = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 1,
        'last_attempted_at' => now(),
        'stripe_session_id' => 'cs_test_success',
        'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
    ]);

    $sessionMock = Mockery::mock();
    $sessionMock->payment_status = 'paid';
    $sessionMock->payment_intent = 'pi_test_success';

    $sessionClassMock = Mockery::mock('alias:Stripe\Checkout\Session');
    $sessionClassMock->shouldReceive('retrieve')
        ->once()
        ->with('cs_test_success')
        ->andReturn($sessionMock);

    actingAs($user)
        ->get(route('payment.success', $order).'?session_id=cs_test_success')
        ->assertRedirect(route('payment.success.show', $order));

    $paymentAttempt->refresh();
    $order->refresh();

    expect($paymentAttempt->status)->toBe('succeeded')
        ->and($paymentAttempt->stripe_payment_intent_id)->toBe('pi_test_success')
        ->and($paymentAttempt->paid_at)->not->toBeNull();

    expect($order->payment_status)->toBe('paid');

    expect(
        Transaction::query()->where('order_id', $order->id)
            ->where('payment_id', $paymentAttempt->id)
            ->where('status', 'completed')
            ->exists()
    )->toBeTrue();
});

it('marks payment attempt as failed when payment does not complete', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->for($user)->create([
        'payment_status' => 'pending',
        'status' => 'pending',
        'stripe_session_id' => 'cs_test_failed',
    ]);

    $paymentAttempt = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 1,
        'last_attempted_at' => now(),
        'stripe_session_id' => 'cs_test_failed',
        'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
    ]);

    $error = new stdClass();
    $error->message = 'Card declined';

    $sessionMock = Mockery::mock();
    $sessionMock->payment_status = 'unpaid';
    $sessionMock->payment_intent = null;
    $sessionMock->last_payment_error = $error;

    $sessionClassMock = Mockery::mock('alias:Stripe\Checkout\Session');
    $sessionClassMock->shouldReceive('retrieve')
        ->once()
        ->with('cs_test_failed')
        ->andReturn($sessionMock);

    actingAs($user)
        ->get(route('payment.success', $order).'?session_id=cs_test_failed')
        ->assertRedirect(route('payment.failed', $order));

    $paymentAttempt->refresh();

    expect($paymentAttempt->status)->toBe('failed')
        ->and($paymentAttempt->failure_details['message'] ?? null)->toBe('Card declined');

    $order->refresh();
    expect($order->payment_status)->toBe('pending');
});

it('returns latest payment attempt helpers from order', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->for($user)->create([
        'payment_status' => 'pending',
        'status' => 'pending',
    ]);

    $firstAttempt = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'failed',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 1,
        'last_attempted_at' => now()->subMinutes(10),
        'failure_details' => ['message' => 'First failure'],
        'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
    ]);

    $secondAttempt = Payment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'status' => 'succeeded',
        'payment_method' => 'stripe',
        'amount' => $order->total_amount,
        'currency' => $order->currency,
        'attempt_number' => 2,
        'last_attempted_at' => now(),
        'paid_at' => now(),
        'stripe_payment_intent_id' => 'pi_latest',
    ]);

    $order->refresh();

    expect($order->latestPaymentAttempt()?->id)->toBe($secondAttempt->id)
        ->and($order->latestSuccessfulPayment()?->id)->toBe($secondAttempt->id)
        ->and($order->latestFailedPayment()?->id)->toBe($firstAttempt->id);
});
