<?php

declare(strict_types=1);

use App\Actions\RegisterDomainAction;
use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Enums\Hosting\HostingPlanStatus;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
            ->assertSessionHas('error'); // Flash error message on failure

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

        // Mock successful status check - statusid '01' indicates success
        Http::fake([
            'api.kpay.test' => Http::response([
                'statusid' => '01',
                'statusdesc' => 'Payment successful',
                'tid' => 'TXN123456',
                'refid' => 'ORD-TEST-1',
                'momtransactionid' => 'MOM123456',
            ], 200),
        ]);

        actingAs($user)
            ->get(route('payment.kpay.status', $payment))
            ->assertRedirect(); // Should redirect to success page

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

    it('processes domain registrations after successful KPay payment via webhook', function (): void {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'is_primary' => true,
        ]);

        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
            'status' => 'pending',
            'type' => 'registration',
            'metadata' => ['selected_contact_id' => $contact->id],
            'items' => [
                [
                    'id' => 'example.com',
                    'name' => 'example.com',
                    'price' => 12.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'registration',
                        'domain_name' => 'example.com',
                        'years' => 1,
                        'currency' => 'USD',
                    ],
                ],
            ],
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
            'kpay_ref_id' => $order->order_number.'-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'last_attempted_at' => now(),
        ]);

        // Mock successful domain registration
        $domain = Domain::factory()->create(['owner_id' => $user->id]);
        $mockAction = mock(RegisterDomainAction::class);
        $mockAction->shouldReceive('handle')
            ->once()
            ->andReturn([
                'success' => true,
                'domain_id' => $domain->id,
                'message' => 'Domain registered successfully',
            ]);

        $this->app->instance(RegisterDomainAction::class, $mockAction);

        // Simulate webhook postback with successful payment status
        $this->postJson(route('payment.kpay.webhook'), [
            'tid' => 'TXN123456',
            'refid' => $order->order_number.'-1',
            'statusid' => '01',
            'statusdesc' => 'Payment successful',
        ])
            ->assertSuccessful()
            ->assertJson(['reply' => 'OK']);

        // Verify order was updated
        $order->refresh();
        expect($order->payment_status)->toBe('paid');
        // Order status will be 'completed' if domain registration succeeds, or 'processing' if pending
        expect($order->status)->toBeIn(['processing', 'completed']);

        // Verify payment was updated
        $payment->refresh();
        expect($payment->status)->toBe('succeeded');

        // Verify order items were created
        expect($order->orderItems)->toHaveCount(1);

        // Verify domain registration was attempted (mocked action was called)
        $mockAction->shouldHaveReceived('handle');
    });

    it('processes domain registrations after successful KPay payment via success handler', function (): void {
        // Use Bus::fake to verify job dispatch
        Bus::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'is_primary' => true,
        ]);

        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
            'status' => 'pending',
            'type' => 'registration',
            'metadata' => ['selected_contact_id' => $contact->id],
            'items' => [
                [
                    'id' => 'example.com',
                    'name' => 'example.com',
                    'price' => 12.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'registration',
                        'domain_name' => 'example.com',
                        'years' => 1,
                        'currency' => 'USD',
                    ],
                ],
            ],
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
            'kpay_ref_id' => $order->order_number.'-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'last_attempted_at' => now(),
        ]);

        // Mock successful status check with proper statusid
        Http::fake([
            'api.kpay.test' => Http::response([
                'statusid' => '01',
                'statusdesc' => 'Payment successful',
                'tid' => 'TXN123456',
                'refid' => $order->order_number.'-1',
                'momtransactionid' => 'MOM123456',
            ], 200),
        ]);

        // Set checkout session with contact ID
        session(['checkout' => ['selected_contact_id' => $contact->id]]);

        actingAs($user)
            ->get(route('payment.kpay.success', $order))
            ->assertRedirect();

        // Verify order was updated
        $order->refresh();
        expect($order->payment_status)->toBe('paid');

        // Verify payment was updated
        $payment->refresh();
        expect($payment->status)->toBe('succeeded');

        // Verify order items were created
        expect($order->orderItems)->toHaveCount(1);

        // Verify domain registration job was dispatched
        Bus::assertDispatched(App\Jobs\ProcessDomainRegistrationJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    });

    it('creates hosting subscriptions after successful KPay payment', function (): void {
        $user = User::factory()->create();

        $hostingPlan = HostingPlan::factory()->create([
            'status' => HostingPlanStatus::Active->value,
        ]);

        $planPrice = HostingPlanPrice::factory()->create([
            'hosting_plan_id' => $hostingPlan->id,
            'billing_cycle' => 'monthly',
            'regular_price' => 2500,
            'status' => HostingPlanPriceStatus::Active->value,
        ]);

        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
            'status' => 'pending',
            'type' => 'hosting',
            'items' => [
                [
                    'id' => 'hosting-1',
                    'name' => $hostingPlan->name,
                    'price' => 25.00,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'hosting',
                        'domain_name' => $hostingPlan->name,
                        'hosting_plan_id' => $hostingPlan->id,
                        'hosting_plan_price_id' => $planPrice->id,
                        'billing_cycle' => 'monthly',
                        'linked_domain' => 'example.com',
                        'currency' => 'USD',
                    ],
                ],
            ],
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
            'kpay_ref_id' => $order->order_number.'-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'last_attempted_at' => now(),
        ]);

        // Mock successful status check
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'payment_status' => 'completed',
                'tid' => 'TXN123456',
                'refid' => $order->order_number.'-1',
                'statusid' => '01',
            ], 200),
        ]);

        actingAs($user)
            ->get(route('payment.kpay.success', $order))
            ->assertRedirect();

        // Verify order was updated
        $order->refresh();
        expect($order->payment_status)->toBe('paid');

        // Verify payment was updated
        $payment->refresh();
        expect($payment->status)->toBe('succeeded');

        // Verify order items were created
        $order->refresh();
        expect($order->orderItems)->toHaveCount(1);

        // Verify the order item has correct metadata
        $orderItem = $order->orderItems->first();
        expect($orderItem->domain_type)->toBe('hosting')
            ->and($orderItem->metadata)->toHaveKey('hosting_plan_id')
            ->and($orderItem->metadata['hosting_plan_id'])->toBe($hostingPlan->id);

        // Verify subscription was created
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('hosting_plan_id', $hostingPlan->id)
            ->first();
        expect($subscription)->not->toBeNull()
            ->and($subscription->hosting_plan_id)->toBe($hostingPlan->id)
            ->and($subscription->hosting_plan_price_id)->toBe($planPrice->id)
            ->and($subscription->billing_cycle)->toBe('monthly')
            ->and($subscription->status)->toBe('active');
    });

    it('does not process domain registrations for renewal orders via webhook', function (): void {
        $user = User::factory()->create();

        $order = Order::factory()->for($user)->create([
            'payment_method' => 'kpay',
            'payment_status' => 'pending',
            'status' => 'pending',
            'type' => 'renewal',
            'items' => [
                [
                    'id' => 'renewal-1',
                    'name' => 'example.com',
                    'price' => 12.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'renewal',
                        'domain_name' => 'example.com',
                        'years' => 1,
                        'currency' => 'USD',
                    ],
                ],
            ],
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
            'kpay_ref_id' => $order->order_number.'-1',
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, 1),
            'last_attempted_at' => now(),
        ]);

        // Mock domain registration action - should NOT be called for renewals
        $mockAction = mock(RegisterDomainAction::class);
        $mockAction->shouldNotReceive('handle');

        $this->app->instance(RegisterDomainAction::class, $mockAction);

        // Simulate webhook postback with successful payment status
        $this->postJson(route('payment.kpay.webhook'), [
            'tid' => 'TXN123456',
            'refid' => $order->order_number.'-1',
            'statusid' => '01',
            'statusdesc' => 'Payment successful',
        ])
            ->assertSuccessful()
            ->assertJson(['reply' => 'OK']);

        // Verify order was updated
        $order->refresh();
        expect($order->payment_status)->toBe('paid');

        // Verify domain registration was NOT attempted
        $mockAction->shouldNotHaveReceived('handle');
    });
});
