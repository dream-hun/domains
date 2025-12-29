<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\KPayPaymentStatusService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class KPayWebhookController extends Controller
{
    public function __construct(
        private readonly KPayPaymentStatusService $kPayPaymentStatusService
    ) {}

    public function handlePostback(Request $request): Response|JsonResponse
    {
        try {
            $data = $request->all();

            Log::info('KPay postback received', [
                'data' => $data,
            ]);

            $tid = $data['tid'] ?? null;
            $refid = $data['refid'] ?? null;
            $statusid = $data['statusid'] ?? null;
            $statusdesc = $data['statusdesc'] ?? '';

            if (empty($tid) && empty($refid)) {
                Log::warning('KPay postback missing transaction identifiers', [
                    'data' => $data,
                ]);

                return response()->json(['reply' => 'ERROR', 'message' => 'Missing transaction identifiers'], 400);
            }

            // Find payment by transaction identifiers
            $payment = $this->kPayPaymentStatusService->findPaymentByTransactionIds($tid, $refid);

            if (! $payment instanceof Payment) {
                Log::warning('KPay postback payment not found', [
                    'tid' => $tid,
                    'refid' => $refid,
                    'data' => $data,
                ]);

                return response()->json(['reply' => 'ERROR', 'message' => 'Payment not found'], 404);
            }

            $order = $payment->order;

            if (! $order) {
                Log::error('KPay postback order not found for payment', [
                    'payment_id' => $payment->id,
                    'tid' => $tid,
                    'refid' => $refid,
                ]);

                return response()->json(['reply' => 'ERROR', 'message' => 'Order not found'], 404);
            }

            if ($this->kPayPaymentStatusService->isSuccessfulStatus($statusid)) {
                // Refresh payment and order to get latest state before processing
                $payment->refresh();
                $order->refresh();

                // Prevent duplicate processing if payment is already successful
                if ($this->kPayPaymentStatusService->isPaymentAlreadyProcessed($payment, $order)) {
                    Log::info('KPay postback: Payment already processed', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tid' => $tid,
                        'refid' => $refid,
                    ]);

                    return response()->json([
                        'tid' => $tid ?? '',
                        'refid' => $refid ?? '',
                        'reply' => 'OK',
                    ]);
                }

                // Update payment and order status
                $this->kPayPaymentStatusService->updatePaymentStatus(
                    $payment,
                    $order,
                    $statusid,
                    $data
                );

                // Process successful payment (dispatch jobs in background)
                try {
                    $this->kPayPaymentStatusService->processSuccessfulPayment($payment, $order, false);

                    Log::info('KPay postback processed successfully - order updated', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'tid' => $tid,
                        'refid' => $refid,
                    ]);
                } catch (Exception $e) {
                    Log::error('KPay postback order processing failed', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'tid' => $tid ?? '',
                    'refid' => $refid ?? '',
                    'reply' => 'OK',
                ]);
            }

            if ($this->kPayPaymentStatusService->isFailedStatus($statusid)) {
                // Update payment and order status
                $this->kPayPaymentStatusService->updatePaymentStatus(
                    $payment,
                    $order,
                    $statusid,
                    $data
                );

                $order->refresh();
                $payment->refresh();

                Log::info('KPay postback payment failed - order updated', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'statusdesc' => $statusdesc,
                ]);

                return response()->json([
                    'tid' => $tid ?? '',
                    'refid' => $refid ?? '',
                    'reply' => 'OK',
                ]);
            }

            Log::info('KPay postback unknown status', [
                'payment_id' => $payment->id,
                'statusid' => $statusid,
                'statusdesc' => $statusdesc,
            ]);

            return response()->json([
                'tid' => $tid,
                'refid' => $refid,
                'reply' => 'OK',
            ]);

        } catch (Exception $exception) {
            Log::error('KPay postback processing error', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json(['reply' => 'ERROR', 'message' => 'Processing failed'], 500);
        }
    }
}
