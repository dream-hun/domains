<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\TransactionLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\Stripe;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Create necessary roles for user creation
    Role::create(['id' => 1, 'title' => 'Admin']);
    Role::create(['id' => 2, 'title' => 'User']);

    // Set up Stripe configuration for testing
    Config::set('services.payment.stripe.secret_key', 'sk_test_fake_key');
    Config::set('services.payment.stripe.publishable_key', 'pk_test_fake_key');
    Config::set('services.payment.stripe.webhook_secret', 'whsec_test_fake_secret');

    // Use a real TransactionLogger instance since it's final
    $this->transactionLogger = new TransactionLogger();
    $this->paymentService = new PaymentService($this->transactionLogger);
});

it('returns error when Stripe is not configured', function () {
    Config::set('services.payment.stripe.secret_key', null);
    Config::set('services.payment.stripe.publishable_key', null);

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'total_amount' => 100.00,
        'currency' => 'USD',
    ]);

    $result = $this->paymentService->processPayment($order, 'stripe');

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error')
        ->and($result['error'])->toContain('not configured');
});

it('returns error for invalid payment method', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $result = $this->paymentService->processPayment($order, 'invalid_method');

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'Invalid payment method');
});

it('returns error for paypal payment method', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $result = $this->paymentService->processPayment($order, 'paypal');

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error')
        ->and($result['error'])->toContain('PayPal');
});

// Note: The following tests would require extensive Stripe API mocking which is complex
// without proper mocking libraries for non-facade classes. In a real-world scenario,
// you would use Stripe's test mode or create integration tests instead of unit tests
// for deep Stripe API interaction testing.

// For now, we verify:
// 1. Configuration validation works correctly ✓
// 2. Invalid payment method handling works ✓
// 3. PayPal returns not implemented message ✓

// Integration tests or feature tests should cover the full Stripe flow with actual
// API calls to Stripe's test mode.
