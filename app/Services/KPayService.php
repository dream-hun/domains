<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

readonly class KPayService
{
    private string $baseUrl;

    private string $username;

    private string $password;

    private string $retailerId;

    public function __construct()
    {
        $this->baseUrl = mb_rtrim(config('services.payment.kpay.base_url', 'https://pay.esicia.com'), '/');
        $this->username = config('services.payment.kpay.username');
        $this->password = config('services.payment.kpay.password');
        $this->retailerId = config('services.payment.kpay.retailer_id');
    }

    /**
     * Initiate a payment request
     *
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>
     */
    public function initiatePayment(array $paymentData): array
    {
        $payload = [
            'action' => 'pay',
            'msisdn' => $this->normalizeMsisdn($paymentData['msisdn']),
            'email' => $paymentData['email'],
            'details' => $paymentData['details'],
            'refid' => $paymentData['refid'],
            'amount' => (int) $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'RWF',
            'cname' => $paymentData['cname'],
            'cnumber' => $paymentData['cnumber'] ?? $this->normalizeMsisdn($paymentData['msisdn']),
            'pmethod' => $paymentData['pmethod'],
            'retailerid' => $this->retailerId,
            'returl' => $paymentData['returl'],
            'redirecturl' => $paymentData['redirecturl'],
        ];

        // Add card details if provided
        if (isset($paymentData['card_number'])) {
            $payload['card_number'] = $paymentData['card_number'];
        }
        if (isset($paymentData['expiry_date'])) {
            $payload['expiry_date'] = $paymentData['expiry_date'];
        }
        if (isset($paymentData['cvv'])) {
            $payload['cvv'] = $paymentData['cvv'];
        }

        // Add optional logourl if provided
        if (isset($paymentData['logourl'])) {
            $payload['logourl'] = $paymentData['logourl'];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl, $payload);

            $responseData = $response->json();
            $statusCode = $response->status();

            Log::info('K-Pay Payment Initiated', [
                'refid' => $paymentData['refid'],
                'status_code' => $statusCode,
                'response' => $responseData,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $statusCode,
                ];
            }

            return [
                'success' => false,
                'data' => $responseData,
                'status_code' => $statusCode,
                'error' => $responseData['statusdesc'] ?? $responseData['retcode'] ?? 'Payment initiation failed',
            ];
        } catch (ConnectionException $e) {
            Log::error('K-Pay Connection Error', [
                'refid' => $paymentData['refid'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Unable to connect to payment gateway. Please try again later.',
            ];
        } catch (Exception $e) {
            Log::error('K-Pay Payment Error', [
                'refid' => $paymentData['refid'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred. Please contact support.',
            ];
        }
    }

    /**
     * Check payment status
     *
     * @param  string  $tid  Transaction ID
     * @param  string  $refid  Reference ID
     * @return array<string, mixed>
     */
    public function checkPaymentStatus(string $tid, string $refid): array
    {
        $payload = [
            'action' => 'checkstatus',
            'tid' => $tid,
            'refid' => $refid,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl, $payload);

            $responseData = $response->json();
            $statusCode = $response->status();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $statusCode,
                ];
            }

            Log::warning('K-Pay Status Check Failed', [
                'tid' => $tid,
                'refid' => $refid,
                'status_code' => $statusCode,
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'data' => $responseData,
                'status_code' => $statusCode,
                'error' => $responseData['statusdesc'] ?? 'Status check failed',
            ];
        } catch (ConnectionException $e) {
            Log::error('K-Pay Status Check Connection Error', [
                'tid' => $tid,
                'refid' => $refid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Unable to connect to payment gateway.',
            ];
        } catch (Exception $e) {
            Log::error('K-Pay Status Check Error', [
                'tid' => $tid,
                'refid' => $refid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while checking payment status.',
            ];
        }
    }

    private function normalizeMsisdn(string $msisdn): string
    {
        $msisdn = preg_replace('/[\s\-+]/', '', $msisdn);

        if (str_starts_with((string) $msisdn, '0')) {
            $msisdn = '250'.mb_substr((string) $msisdn, 1);
        } elseif (! str_starts_with((string) $msisdn, '250')) {
            $msisdn = '250'.$msisdn;
        }

        return $msisdn;
    }
}
