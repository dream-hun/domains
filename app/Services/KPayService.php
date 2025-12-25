<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class KPayService
{
    private string $baseUrl;

    private string $username;

    private string $password;

    private string $retailerId;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->baseUrl = config('services.payment.kpay.base_url') ?? '';
        $this->username = config('services.payment.kpay.username') ?? '';
        $this->password = config('services.payment.kpay.password') ?? '';
        $this->retailerId = config('services.payment.kpay.retailer_id') ?? '';
    }

    /**
     * @throws Exception
     */
    public function initiatePayment(array $paymentData): array
    {
        $msisdn = $this->normalizeMsisdn($paymentData['msisdn']);

        // Ensure refid is not empty
        $refid = mb_trim((string) ($paymentData['ref_id'] ?? $paymentData['refid'] ?? ''));
        if (empty($refid)) {
            throw new Exception('Reference ID (refid) is required for KPay payment');
        }

        // Ensure retailer ID is not empty
        if (empty($this->retailerId)) {
            throw new Exception('Retailer ID is not configured for KPay');
        }

        // Validate amount is greater than 0
        $amount = (int) round((float) ($paymentData['amount'] ?? 0));
        if ($amount <= 0) {
            throw new Exception('Payment amount must be greater than 0');
        }

        // Ensure all values are properly formatted strings and not empty
        $payload = [
            'action' => 'pay',
            'msisdn' => mb_trim($msisdn),
            'email' => mb_trim((string) ($paymentData['email'] ?? '')),
            'details' => mb_trim((string) ($paymentData['details'] ?? '')),
            'refid' => mb_trim($refid),
            'amount' => (string) $amount,
            'currency' => mb_strtoupper(mb_trim((string) ($paymentData['currency'] ?? 'RWF'))),
            'cname' => mb_trim((string) ($paymentData['cname'] ?? '')),
            'cnumber' => mb_trim((string) ($paymentData['cnumber'] ?? $msisdn)),
            'pmethod' => mb_trim((string) ($paymentData['pmethod'] ?? 'mobile_money')),
            'retailerid' => mb_trim((string) $this->retailerId),
            'returl' => mb_trim((string) ($paymentData['returl'] ?? '')),
            'redirecturl' => mb_trim((string) ($paymentData['redirecturl'] ?? $paymentData['returl'] ?? '')),
        ];

        if (isset($paymentData['logourl'])) {
            $payload['logourl'] = $paymentData['logourl'];
        }

        // Log the payload for debugging (without sensitive data)
        Log::debug('K-Pay Payment Payload', [
            'action' => $payload['action'],
            'msisdn' => $msisdn,
            'msisdn_length' => mb_strlen($payload['msisdn']),
            'email' => mb_substr($payload['email'], 0, 20).'...',
            'email_length' => mb_strlen($payload['email']),
            'details' => $payload['details'],
            'details_length' => mb_strlen($payload['details']),
            'refid' => $payload['refid'],
            'refid_length' => mb_strlen($payload['refid']),
            'amount' => $payload['amount'],
            'amount_type' => gettype($payload['amount']),
            'currency' => $payload['currency'],
            'cname' => $payload['cname'],
            'cname_length' => mb_strlen($payload['cname']),
            'cnumber' => $payload['cnumber'],
            'pmethod' => $payload['pmethod'],
            'retailerid' => $payload['retailerid'],
            'retailerid_length' => mb_strlen($payload['retailerid']),
            'returl' => $payload['returl'],
            'redirecturl' => $payload['redirecturl'],
            'has_retailerid' => ! empty($payload['retailerid']),
            'all_payload_keys' => array_keys($payload),
            'payload_count' => count($payload),
        ]);

        try {
            // Validate all required fields are present and not empty
            $requiredFields = ['msisdn', 'email', 'details', 'refid', 'amount', 'cname', 'pmethod', 'retailerid', 'returl'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                $value = $payload[$field] ?? null;
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $missingFields[] = $field.' (value: "'.($value ?? 'null').'")';
                }
            }

            if (! empty($missingFields)) {
                Log::error('K-Pay Missing Required Fields', [
                    'missing_fields' => $missingFields,
                    'payload_keys' => array_keys($payload),
                    'payload_values' => array_map(fn ($v) => is_string($v) ? mb_substr($v, 0, 50) : $v, $payload),
                ]);
                throw new Exception('Missing required fields: '.implode(', ', $missingFields));
            }

            // Use JSON format (most APIs expect this)
            // Log the exact payload being sent
            Log::info('K-Pay Sending Request', [
                'url' => $this->baseUrl,
                'payload' => $payload,
                'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'username_set' => ! empty($this->username),
                'password_set' => ! empty($this->password),
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl, $payload);

            // Log the raw response
            Log::debug('K-Pay Raw Response', [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);
            $responseData = $response->json();
            $statusCode = $response->status();

            // Log full request and response for debugging
            Log::info('K-Pay Payment Initiated', [
                'ref_id' => $refid,
                'status_code' => $statusCode,
                'response' => $responseData,
                'payload_keys' => array_keys($payload),
                'retailerid_set' => ! empty($payload['retailerid']),
                'refid_set' => ! empty($payload['refid']),
            ]);

            // Check if response indicates success (some APIs return 200 but with success:0)
            $isSuccess = $response->successful() &&
                        (($responseData['success'] ?? $responseData['retcode'] ?? null) === 1 ||
                         ($responseData['success'] ?? null) === true ||
                         ($responseData['retcode'] ?? null) === '000' ||
                         ($responseData['retcode'] ?? null) === 0);

            if ($isSuccess) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status_code' => $statusCode,
                ];
            }

            // Extract error message from response
            $errorMessage = $responseData['reply'] ??
                          $responseData['statusdesc'] ??
                          $responseData['message'] ??
                          ($responseData['retcode'] ? 'Error code: '.$responseData['retcode'] : 'Payment initiation failed');

            return [
                'success' => false,
                'data' => $responseData,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ];
        } catch (ConnectionException $e) {
            Log::error('K-Pay Connection Error', [
                'ref_id' => $paymentData['ref_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Unable to connect to payment gateway. Please try again later.',
            ];
        } catch (Exception $e) {
            Log::error('K-Pay Payment Error', [
                'ref_id' => $paymentData['ref_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred. Please contact support.',
            ];
        }
    }

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

    /**
     * Normalize MSISDN format - Remove + sign as per API requirements
     */
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
