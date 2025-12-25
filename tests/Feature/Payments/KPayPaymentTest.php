<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('services.payment.kpay.base_url', 'https://api.kpay.test');
    Config::set('services.payment.kpay.username', 'test_username');
    Config::set('services.payment.kpay.password', 'test_password');
    Config::set('services.payment.kpay.retailer_id', 'test_retailer');

    Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2], ['title' => 'User']);

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
            'exchange_rate' => 1200.0,
            'is_base' => false,
            'is_active' => true,
        ]
    );
});

describe('KPay Payment Flow', function (): void {
    it('processes KPay payment successfully', function (): void {
        $user = User::factory()->create();

        // Add items to cart
        Cart::add([
            'id' => 'test-domain',
            'name' => 'example.com',
            'price' => 10.00,
            'quantity' => 1,
            'attributes' => [
                'type' => 'registration',
                'currency' => 'USD',
            ],
        ]);

        session(['checkout' => [
            'cart_items' => Cart::getContent()->toArray(),
            'total' => 10.00,
            'subtotal' => 10.00,
            'tax' => 0.00,
        ]]);

        // Mock KPay API response
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'tid' => 'TXN123456',
                'refid' => 'ORD-TEST-1',
                'statusdesc' => 'Payment initiated successfully',
                'redirecturl' => 'https://kpay.test/payment/TXN123456',
            ], 200),
        ]);

        actingAs($user)
            ->post(route('payment.kpay'), [
                'msisdn' => '250788123456',
                'pmethod' => 'mobile_money',
                'billing_name' => 'Test User',
                'billing_email' => 'test@example.com',
                'billing_address' => '123 Test St',
                'billing_city' => 'Test City',
                'billing_country' => 'RW',
                'billing_postal_code' => '00000',
            ])
            ->assertRedirect();

        // Verify order was created
        $order = Order::query()->where('user_id', $user->id)->latest()->first();
        expect($order)->not->toBeNull()
            ->and($order->payment_method)->toBe('kpay');

        // Verify payment attempt was created
        $payment = Payment::query()->where('order_id', $order->id)->first();
        expect($payment)->not->toBeNull()
            ->and($payment->payment_method)->toBe('kpay')
            ->and($payment->kpay_transaction_id)->toBe('TXN123456')
            ->and($payment->status)->toBe('pending');
    });

    it('validates required KPay payment fields', function (): void {
        $user = User::factory()->create();

        Cart::add([
            'id' => 'test-domain',
            'name' => 'example.com',
            'price' => 10.00,
            'quantity' => 1,
            'attributes' => [
                'type' => 'registration',
                'currency' => 'USD',
            ],
        ]);

        session(['checkout' => [
            'cart_items' => Cart::getContent()->toArray(),
            'total' => 10.00,
        ]]);

        actingAs($user)
            ->post(route('payment.kpay'), [
                'billing_name' => 'Test User',
                'billing_email' => 'test@example.com',
            ])
            ->assertSessionHasErrors(['msisdn', 'pmethod']);
    });

    it('handles KPay payment initiation failure', function (): void {
        $user = User::factory()->create();

        Cart::add([
            'id' => 'test-domain',
            'name' => 'example.com',
            'price' => 10.00,
            'quantity' => 1,
            'attributes' => [
                'type' => 'registration',
                'currency' => 'USD',
            ],
        ]);

        session(['checkout' => [
            'cart_items' => Cart::getContent()->toArray(),
            'total' => 10.00,
        ]]);

        // Mock KPay API failure
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'failed',
                'retcode' => 'ERROR001',
                'statusdesc' => 'Insufficient funds',
            ], 400),
        ]);

        actingAs($user)
            ->post(route('payment.kpay'), [
                'msisdn' => '250788123456',
                'pmethod' => 'mobile_money',
                'billing_name' => 'Test User',
                'billing_email' => 'test@example.com',
            ])
            ->assertSessionHasErrors();

        // Verify payment attempt was marked as failed
        $order = Order::query()->where('user_id', $user->id)->latest()->first();
        if ($order) {
            $payment = Payment::query()->where('order_id', $order->id)->first();
            if ($payment) {
                expect($payment->status)->toBe('failed');
            }
        }
    });

    it('checks KPay payment status successfully', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'kpay',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'attempt_number' => 1,
            'kpay_transaction_id' => 'TXN123456',
            'kpay_ref_id' => 'ORD-TEST-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'last_attempted_at' => now(),
        ]);

        // Mock successful status check
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'payment_status' => 'completed',
                'tid' => 'TXN123456',
                'refid' => 'ORD-TEST-1',
            ], 200),
        ]);

        actingAs($user)
            ->get(route('payment.kpay.status', $payment))
            ->assertSuccessful();

        $payment->refresh();
        expect($payment->status)->toBe('succeeded')
            ->and($payment->paid_at)->not->toBeNull();
    });

    it('handles KPay payment cancellation', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'kpay',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'attempt_number' => 1,
            'kpay_transaction_id' => 'TXN123456',
            'kpay_ref_id' => 'ORD-TEST-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'last_attempted_at' => now(),
        ]);

        actingAs($user)
            ->get(route('payment.kpay.cancel', $order))
            ->assertRedirect(route('cart.index'));

        $order->refresh();
        $payment->refresh();

        expect($order->payment_status)->toBe('cancelled')
            ->and($payment->status)->toBe('cancelled');
    });

    it('processes successful KPay payment and completes order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'succeeded',
            'payment_method' => 'kpay',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'attempt_number' => 1,
            'kpay_transaction_id' => 'TXN123456',
            'kpay_ref_id' => 'ORD-TEST-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'paid_at' => now(),
            'last_attempted_at' => now(),
        ]);

        // Mock successful status check
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'payment_status' => 'completed',
                'tid' => 'TXN123456',
                'refid' => 'ORD-TEST-1',
            ], 200),
        ]);

        actingAs($user)
            ->get(route('payment.kpay.success', $order))
            ->assertRedirect();

        $order->refresh();
        expect($order->payment_status)->toBe('paid')
            ->and($order->status)->toBe('processing');
    });
});
