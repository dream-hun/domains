<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class KPayPaymentStatusService
{
    public function __construct(
        private ProcessOrderAfterPaymentAction $processOrderAction
    ) {}

    /**
     * Find payment by KPay transaction identifiers
     */
    public function findPaymentByTransactionIds(?string $tid, ?string $refid): ?Payment
    {
        $query = Payment::query()->where('payment_method', 'kpay');

        if ($tid) {
            $query->where('kpay_transaction_id', $tid);
        }

        if ($refid) {
            $query->orWhere('kpay_ref_id', $refid);
        }

        /** @var Payment|null */
        return $query->first();
    }

    /**
     * Check if statusid indicates successful payment
     * KPay may return different values for success
     */
    public function isSuccessfulStatus(?string $statusid): bool
    {
        if ($statusid === null) {
            return false;
        }

        $successStatuses = ['01', '1', 'SUCCESS', 'SUCCESSFUL', 'OK', 'COMPLETED', 'APPROVED', '0'];

        return in_array($statusid, $successStatuses, true) || $statusid === 1 || $statusid === 0;
    }

    /**
     * Check if statusid indicates failed payment
     * KPay may return different values for failure
     */
    public function isFailedStatus(?string $statusid): bool
    {
        if ($statusid === null) {
            return false;
        }

        $failedStatuses = ['02', '2', 'FAILED', 'FAILURE', 'ERROR', 'DECLINED', 'REJECTED'];

        return in_array($statusid, $failedStatuses, true) || $statusid === 2;
    }

    /**
     * Check if payment/order is already processed
     */
    public function isPaymentAlreadyProcessed(Payment $payment, Order $order): bool
    {
        return $payment->isSuccessful() && ($order->isPaid() || $order->isProcessing() || $order->isCompleted());
    }

    /**
     * Update payment and order status based on KPay callback
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePaymentStatus(Payment $payment, Order $order, ?string $statusid, array $data = []): void
    {
        $paymentStatus = 'pending';
        $orderPaymentStatus = 'pending';
        $orderStatus = 'pending';

        if ($this->isSuccessfulStatus($statusid)) {
            $paymentStatus = 'succeeded';
            $orderPaymentStatus = 'paid';
            $orderStatus = 'processing';
        } elseif ($this->isFailedStatus($statusid)) {
            $paymentStatus = 'failed';
            $orderPaymentStatus = 'failed';
            $orderStatus = 'failed';
        }

        $payment->update([
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'succeeded' ? now() : null,
            'metadata' => array_merge($payment->metadata ?? [], [
                'kpay_callback_data' => $data,
                'kpay_statusid' => $statusid,
                'kpay_statusdesc' => $data['statusdesc'] ?? null,
                'kpay_momtransactionid' => $data['momtransactionid'] ?? null,
            ]),
            'last_attempted_at' => now(),
        ]);

        $order->update([
            'payment_status' => $orderPaymentStatus,
            'status' => $orderStatus,
        ]);
    }

    /**
     * Process successful payment - trigger domain registration, subscription creation, etc.
     */
    public function processSuccessfulPayment(Payment $payment, Order $order, bool $clearCart = true): void
    {
        try {
            // Get contact IDs from order metadata
            $contactIds = [];
            if (isset($order->metadata['contact_ids'])) {
                $contactIds = $order->metadata['contact_ids'];
            } elseif (isset($order->metadata['selected_contact_id'])) {
                $contactId = $order->metadata['selected_contact_id'];
                $contactIds = [
                    'registrant' => $contactId,
                    'admin' => $contactId,
                    'tech' => $contactId,
                    'billing' => $contactId,
                ];
            }

            $this->processOrderAction->handle($order, $contactIds, $clearCart);

            Log::info('KPay payment processed successfully via status service', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

        } catch (Exception $exception) {
            Log::error('Error processing KPay payment via status service', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
