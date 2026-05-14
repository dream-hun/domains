<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final readonly class PawaPayService
{
    private string $baseUrl;

    private string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.payment.pawapay.base_url');
        $this->token = config('services.payment.pawapay.token');
    }

    // -------------------------------------------------------
    // PREDICT PROVIDER (validate & clean phone number)
    // -------------------------------------------------------

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function predictProvider(string $phoneNumber): array
    {
        return $this->client()
            ->post('/v2/predict-provider', ['phoneNumber' => $phoneNumber])
            ->throw()
            ->json();
    }

    // -------------------------------------------------------
    // DEPOSITS
    // -------------------------------------------------------

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function initiateDeposit(
        string $depositId,
        string $amount,
        string $currency,
        string $phoneNumber,
        string $provider,
        array $extra = []
    ): array {
        $payload = array_merge([
            'depositId' => $depositId,
            'amount' => $amount,
            'currency' => $currency,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $phoneNumber,
                    'provider' => $provider,
                ],
            ],
        ], $extra);

        return $this->client()
            ->post('/v2/deposits', $payload)
            ->throwIfServerError()
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function checkDepositStatus(string $depositId): array
    {
        return $this->client()
            ->get("/v2/deposits/$depositId")
            ->throw()
            ->json();
    }

    // -------------------------------------------------------
    // PAYOUTS
    // -------------------------------------------------------

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function initiatePayout(
        string $payoutId,
        string $amount,
        string $currency,
        string $phoneNumber,
        string $provider
    ): array {
        return $this->client()
            ->post('/v2/payouts', [
                'payoutId' => $payoutId,
                'amount' => $amount,
                'currency' => $currency,
                'recipient' => [
                    'type' => 'MMO',
                    'accountDetails' => [
                        'phoneNumber' => $phoneNumber,
                        'provider' => $provider,
                    ],
                ],
            ])
            ->throwIfServerError()
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function checkPayoutStatus(string $payoutId): array
    {
        return $this->client()
            ->get("/v2/payouts/$payoutId")
            ->throw()
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function cancelEnqueuedPayout(string $payoutId): array
    {
        return $this->client()
            ->post("/v2/payouts/fail-enqueued/$payoutId")
            ->throw()
            ->json();
    }

    // -------------------------------------------------------
    // REFUNDS
    // -------------------------------------------------------

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function initiateRefund(
        string $refundId,
        string $depositId,
        string $amount
    ): array {
        return $this->client()
            ->post('/v2/refunds', [
                'refundId' => $refundId,
                'depositId' => $depositId,
                'amount' => $amount,
            ])
            ->throwIfServerError()
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function checkRefundStatus(string $refundId): array
    {
        return $this->client()
            ->get("/v2/refunds/$refundId")
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10);
    }
}
