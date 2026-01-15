<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KPayCurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Mock fallback rate for CurrencyExchangeHelper if it's used
        Config::set('currency_exchange.fallback_rates.USD_TO_RWF', 1200.0);
        Config::set('currency_exchange.supported_currencies.RWF', ['symbol' => 'FRW']);
    }

    public function test_kpay_payment_converts_usd_to_rwf(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 200.00,
            'currency' => 'USD',
            'payment_method' => 'kpay',
        ]);

        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'tid' => 'TXN123',
                'refid' => 'REF123',
            ], 200),
        ]);

        $paymentService = resolve(PaymentService::class);
        $result = $paymentService->processKPayPayment($order, [
            'msisdn' => '250788123456',
            'pmethod' => 'momo',
        ]);

        if (! ($result['success'] ?? false)) {
            $this->fail('Payment failed: '.($result['error'] ?? 'Unknown error'));
        }

        Http::assertSent(function ($request): bool {
            $data = $request->data();
            file_put_contents('php://stderr', print_r($data, true));

            // 200 USD * 1200 rate = 240,000 RWF
            return ($data['amount'] ?? null) === 240000 && ($data['currency'] ?? null) === 'RWF';
        });
    }
}
