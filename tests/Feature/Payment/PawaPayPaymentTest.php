<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.payment.pawapay.token' => 'test-token',
        'services.payment.pawapay.base_url' => 'https://api.sandbox.pawapay.io',
    ]);
});

test('PaymentService resolves without error when PawaPay is not configured', function (): void {
    config([
        'services.payment.pawapay.token' => null,
        'services.payment.pawapay.base_url' => null,
    ]);

    expect(fn () => app(PaymentService::class))->not->toThrow(TypeError::class);
});

test('user without cart is redirected to checkout from PawaPay payment page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('payment.mobile-money.show'));

    $response->assertRedirect(route('checkout.index'));
});

test('user with existing pending order can view PawaPay payment page', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'payment_method' => 'pawapay',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    session(['pawapay_order_number' => $order->order_number]);

    $response = $this->actingAs($user)->get(route('payment.mobile-money.show'));

    $response->assertStatus(200);
    $response->assertViewIs('payment.mobile-money');
});

test('PawaPay deposit initiation succeeds with ACCEPTED response', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'payment_method' => 'pawapay',
        'currency' => 'RWF',
        'total_amount' => 5000,
    ]);

    Http::fake([
        '*predict-provider*' => Http::response([['provider' => 'MTN']], 200),
        '*deposits*' => Http::response(['depositId' => 'uuid-123', 'status' => 'ACCEPTED'], 200),
    ]);

    session(['pawapay_order_number' => $order->order_number]);

    $response = $this->actingAs($user)->postJson(route('payment.mobile-money'), [
        'msisdn' => '250788000000',
        'billing_name' => $user->name,
        'billing_email' => $user->email,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['success' => true, 'requires_action' => true]);

    expect(Payment::query()->where('order_id', $order->id)->where('payment_method', 'pawapay')->exists())->toBeTrue();
    expect(Payment::query()->where('order_id', $order->id)->whereNotNull('pawapay_deposit_id')->exists())->toBeTrue();
});

test('PawaPay deposit initiation fails when provider cannot be determined', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'payment_method' => 'pawapay',
        'currency' => 'RWF',
        'total_amount' => 5000,
    ]);

    Http::fake([
        '*predict-provider*' => Http::response([['provider' => null]], 200),
    ]);

    session(['pawapay_order_number' => $order->order_number]);

    $response = $this->actingAs($user)->postJson(route('payment.mobile-money'), [
        'msisdn' => '250788000000',
        'billing_name' => $user->name,
        'billing_email' => $user->email,
    ]);

    $response->assertStatus(400);
    $response->assertJsonStructure(['error']);
});

test('PawaPay status check returns pending when deposit is processing', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'deposit-uuid-abc',
    ]);

    Http::fake([
        '*deposits/deposit-uuid-abc*' => Http::response([['status' => 'PROCESSING']], 200),
    ]);

    $response = $this->actingAs($user)->getJson(route('payment.mobile-money.status', $payment));

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'pending']);
});

test('PawaPay status check marks payment succeeded when deposit completed', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => 'pending',
        'payment_method' => 'pawapay',
        'pawapay_deposit_id' => 'deposit-uuid-def',
    ]);

    Http::fake([
        '*deposits/deposit-uuid-def*' => Http::response([['status' => 'COMPLETED']], 200),
    ]);

    $response = $this->actingAs($user)->getJson(route('payment.mobile-money.status', $payment));

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'succeeded']);

    $payment->refresh();
    expect($payment->status)->toBe('succeeded');
});

test('user cannot access another users PawaPay payment status', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id]);
    $payment = Payment::factory()->create([
        'user_id' => $otherUser->id,
        'order_id' => $order->id,
        'payment_method' => 'pawapay',
    ]);

    $response = $this->actingAs($user)->get(route('payment.mobile-money.status', $payment));

    $response->assertRedirect(route('dashboard'));
});

test('PawaPay payment validation requires msisdn', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('payment.mobile-money'), [
        'billing_name' => 'Test User',
        'billing_email' => 'test@example.com',
    ])->assertStatus(422)->assertJsonValidationErrors('msisdn');
});

test('PawaPay payment is rejected when order currency is not RWF', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'payment_method' => 'pawapay',
        'currency' => 'USD',
        'total_amount' => 10,
    ]);

    Http::fake([
        '*predict-provider*' => Http::response([['provider' => 'MTN']], 200),
    ]);

    session(['pawapay_order_number' => $order->order_number]);

    $response = $this->actingAs($user)->postJson(route('payment.mobile-money'), [
        'msisdn' => '250788000000',
        'billing_name' => $user->name,
        'billing_email' => $user->email,
    ]);

    $response->assertStatus(400);
    $response->assertJsonFragment(['error' => 'PawaPay only supports RWF orders. Please select a different payment method.']);

    Http::assertNothingSent();
});

test('PawaPay payment validation requires billing_name', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('payment.mobile-money'), [
        'msisdn' => '250788000000',
        'billing_email' => 'test@example.com',
    ])->assertStatus(422)->assertJsonValidationErrors('billing_name');
});

test('PawaPay cancel sets session and redirects back to payment form', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'payment_method' => 'pawapay',
    ]);

    $response = $this->actingAs($user)->get(route('payment.mobile-money.cancel', $order));

    $response->assertRedirect(route('payment.mobile-money.show'));
    expect(session('pawapay_order_number'))->toBe($order->order_number);
});
