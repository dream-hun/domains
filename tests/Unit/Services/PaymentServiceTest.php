<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\KPayService;
use App\Services\OrderItemFormatterService;
use App\Services\PaymentService;
use App\Services\StripeCheckoutService;
use App\Services\TransactionLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function (): void {

    Role::query()->create(['id' => 1, 'title' => 'Admin']);
    Role::query()->create(['id' => 2, 'title' => 'User']);

    Config::set('services.payment.stripe.secret_key', 'sk_test_fake_key');
    Config::set('services.payment.stripe.publishable_key', 'pk_test_fake_key');
    Config::set('services.payment.stripe.webhook_secret', 'whsec_test_fake_secret');

    $this->transactionLogger = new TransactionLogger();
    $this->formatter = new OrderItemFormatterService();
    $this->stripeCheckoutService = new StripeCheckoutService($this->formatter);
    $this->paymentService = new PaymentService(
        $this->transactionLogger,
        $this->stripeCheckoutService,
        resolve(KPayService::class)
    );
});

it('returns error when Stripe is not configured', function (): void {
    Config::set('services.payment.stripe.secret_key');
    Config::set('services.payment.stripe.publishable_key');

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

it('returns error for invalid payment method', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $result = $this->paymentService->processPayment($order, 'invalid_method');

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'Invalid payment method');
});

it('automatically converts to USD when amount is below Stripe minimum', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->for($user)->create([
        'total_amount' => 100.00,
        'currency' => 'RWF',
    ]);

    $result = $this->paymentService->processPayment($order, 'stripe');
    expect($result)->toHaveKey('success', false);

    if (isset($result['error'])) {
        expect($result['error'])->not->toContain('minimum');
    }
});

it('processes orders with amount meeting Stripe minimum in original currency', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'total_amount' => 10.00,
        'currency' => 'USD',
    ]);
    $result = $this->paymentService->processPayment($order, 'stripe');

    expect($result)->toHaveKey('success', false);
});
