<?php

declare(strict_types=1);

use App\Services\KPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('services.payment.kpay.base_url', 'https://api.kpay.test');
    Config::set('services.payment.kpay.username', 'test_username');
    Config::set('services.payment.kpay.password', 'test_password');
    Config::set('services.payment.kpay.retailer_id', 'test_retailer');

    $this->service = new KPayService();
});

describe('initiatePayment', function (): void {
    it('successfully initiates payment with valid data', function (): void {
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'tid' => 'TXN123456',
                'refid' => 'ORD-12345-1',
                'statusdesc' => 'Payment initiated successfully',
            ], 200),
        ]);

        $paymentData = [
            'msisdn' => '250788123456',
            'email' => 'test@example.com',
            'details' => 'Test order',
            'ref_id' => 'ORD-12345-1',
            'amount' => 1000,
            'currency' => 'RWF',
            'cname' => 'Test User',
            'cnumber' => '250788123456',
            'pmethod' => 'mobile_money',
            'returl' => 'https://example.com/return',
            'redirecturl' => 'https://example.com/redirect',
        ];

        $result = $this->service->initiatePayment($paymentData);

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['data'])->toHaveKey('tid')
            ->and($result['data']['tid'])->toBe('TXN123456');
    });

    it('handles payment initiation failure', function (): void {
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'failed',
                'retcode' => 'ERROR001',
                'statusdesc' => 'Insufficient funds',
            ], 400),
        ]);

        $paymentData = [
            'msisdn' => '250788123456',
            'email' => 'test@example.com',
            'details' => 'Test order',
            'ref_id' => 'ORD-12345-1',
            'amount' => 1000,
            'currency' => 'RWF',
            'cname' => 'Test User',
            'cnumber' => '250788123456',
            'pmethod' => 'mobile_money',
            'returl' => 'https://example.com/return',
            'redirecturl' => 'https://example.com/redirect',
        ];

        $result = $this->service->initiatePayment($paymentData);

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result['error'])->toBeString();
    });

    it('handles connection errors gracefully', function (): void {
        Http::fake([
            'api.kpay.test' => Http::response([], 500),
        ]);

        $paymentData = [
            'msisdn' => '250788123456',
            'email' => 'test@example.com',
            'details' => 'Test order',
            'ref_id' => 'ORD-12345-1',
            'amount' => 1000,
            'currency' => 'RWF',
            'cname' => 'Test User',
            'cnumber' => '250788123456',
            'pmethod' => 'mobile_money',
            'returl' => 'https://example.com/return',
            'redirecturl' => 'https://example.com/redirect',
        ];

        $result = $this->service->initiatePayment($paymentData);

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result['error'])->toBeString();
    });

    it('normalizes MSISDN correctly', function (): void {
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'tid' => 'TXN123456',
            ], 200),
        ]);

        $paymentData = [
            'msisdn' => '+250 788 123 456', // With spaces and +
            'email' => 'test@example.com',
            'details' => 'Test order',
            'ref_id' => 'ORD-12345-1',
            'amount' => 1000,
            'currency' => 'RWF',
            'cname' => 'Test User',
            'cnumber' => '250788123456',
            'pmethod' => 'mobile_money',
            'returl' => 'https://example.com/return',
            'redirecturl' => 'https://example.com/redirect',
        ];

        $result = $this->service->initiatePayment($paymentData);

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue();

        // Verify the normalized MSISDN was sent (check HTTP request)
        Http::assertSent(function ($request): bool {
            $body = $request->body();
            $data = json_decode($body, true);

            return isset($data['msisdn']) && $data['msisdn'] === '250788123456';
        });
    });
});

describe('checkPaymentStatus', function (): void {
    it('successfully checks payment status', function (): void {
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'success',
                'payment_status' => 'completed',
                'tid' => 'TXN123456',
                'refid' => 'ORD-12345-1',
            ], 200),
        ]);

        $result = $this->service->checkPaymentStatus('TXN123456', 'ORD-12345-1');

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['data'])->toHaveKey('payment_status')
            ->and($result['data']['payment_status'])->toBe('completed');
    });

    it('handles status check failure', function (): void {
        Http::fake([
            'api.kpay.test' => Http::response([
                'status' => 'failed',
                'statusdesc' => 'Transaction not found',
            ], 404),
        ]);

        $result = $this->service->checkPaymentStatus('INVALID', 'INVALID');

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result['error'])->toBeString();
    });

    it('handles connection errors during status check', function (): void {
        Http::fake([
            'api.kpay.test' => function (): void {
                throw new ConnectionException('Connection timeout');
            },
        ]);

        $result = $this->service->checkPaymentStatus('TXN123456', 'ORD-12345-1');

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result['error'])->toContain('Unable to connect');
    });
});
