<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const TEST_PAWAPAY_WEBHOOK_SECRET = 'test-webhook-secret';

beforeEach(function (): void {
    config(['services.payment.pawapay.webhook_secret' => TEST_PAWAPAY_WEBHOOK_SECRET]);
});

function pawaPayWebhookPost(mixed $test, string $route, array $payload): Illuminate\Testing\TestResponse
{
    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $body, TEST_PAWAPAY_WEBHOOK_SECRET);

    return $test->postJson($route, $payload, ['X-PawaPay-Signature' => $signature]);
}

test('webhook marks payment as succeeded on COMPLETED status', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'test-deposit-123',
    ]);

    $response = pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'depositId' => 'test-deposit-123',
        'status' => 'COMPLETED',
        'amount' => '5000',
        'currency' => 'RWF',
        'payer' => ['accountDetails' => ['phoneNumber' => '250788000000']],
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'ok']);

    $payment->refresh();
    expect($payment->status)->toBe('succeeded');

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
});

test('webhook marks payment as failed on FAILED status', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'test-deposit-456',
    ]);

    $response = pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'depositId' => 'test-deposit-456',
        'status' => 'FAILED',
    ]);

    $response->assertStatus(200);

    $payment->refresh();
    expect($payment->status)->toBe('failed');

    $order->refresh();
    expect($order->payment_status)->toBe('failed');
});

test('webhook marks payment as failed on TIMED_OUT status', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'test-deposit-789',
    ]);

    pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'depositId' => 'test-deposit-789',
        'status' => 'TIMED_OUT',
    ])->assertStatus(200);

    $payment->refresh();
    expect($payment->status)->toBe('failed');
});

test('webhook returns 404 for unknown deposit ID', function (): void {
    pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'depositId' => 'nonexistent-deposit',
        'status' => 'COMPLETED',
    ])->assertStatus(404);
});

test('webhook is idempotent for already processed payments', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->succeeded()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'test-deposit-idempotent',
    ]);

    $response = pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'depositId' => 'test-deposit-idempotent',
        'status' => 'COMPLETED',
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'ok']);
});

test('webhook returns 400 when depositId is missing', function (): void {
    pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'status' => 'COMPLETED',
    ])->assertStatus(400);
});

test('webhook handles DUPLICATE_IGNORED status without error', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);
    Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'test-deposit-dup',
    ]);

    pawaPayWebhookPost($this, route('webhooks.pawapay'), [
        'depositId' => 'test-deposit-dup',
        'status' => 'DUPLICATE_IGNORED',
    ])->assertStatus(200)->assertJsonFragment(['status' => 'ok']);
});
