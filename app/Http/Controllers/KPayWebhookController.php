<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Models\Order;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class KPayWebhookController extends Controller
{
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

            $payment = null;
            if ($refid) {
                $parts = explode('-', (string) $refid);
                if (count($parts) >= 2) {
                    $orderNumber = implode('-', array_slice($parts, 0, -1));
                    $order = Order::query()->where('order_number', $orderNumber)->first();

                    if ($order) {
                        $payment = $order->payments()
                            ->where('kpay_ref_id', $refid)
                            ->orWhere('kpay_transaction_id', $tid)
                            ->orderByDesc('attempt_number')
                            ->orderByDesc('id')
                            ->first();
                    }
                }
            }

            if (! $payment) {
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

            if ($statusid === '01' || $statusid === 1) {
                DB::transaction(function () use ($payment, $order, $data): void {
                    if (! $payment->isSuccessful()) {
                        $payment->update([
                            'status' => 'succeeded',
                            'paid_at' => now(),
                            'metadata' => array_merge($payment->metadata ?? [], [
                                'kpay_postback' => $data,
                                'kpay_momtransactionid' => $data['momtransactionid'] ?? null,
                            ]),
                        ]);
                    }

                    if ($order->payment_status !== 'paid') {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                            'payment_method' => 'kpay',
                            'processed_at' => now(),
                        ]);
                    }
                });

                try {
                    $orderMetadata = $order->metadata ?? [];
                    $contactId = $orderMetadata['selected_contact_id']
                        ?? $orderMetadata['contact_ids']['registrant'] ?? null
                        ?? $order->user->contacts()->where('is_primary', true)->first()?->id;

                    $contactIds = [];
                    if ($contactId) {
                        $contactIds = [
                            'registrant' => $contactId,
                            'admin' => $contactId,
                            'tech' => $contactId,
                            'billing' => $contactId,
                        ];
                    }

                    $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                    try {
                        $processOrderAction->handle($order, $contactIds, false);
                    } catch (Exception $e) {
                        Log::warning('Order processing failed in KPay webhook handler, will be retried by success handler', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::info('KPay postback processed successfully', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'tid' => $tid,
                        'refid' => $refid,
                    ]);

                } catch (Exception $e) {
                    Log::error('KPay postback order processing failed', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'tid' => $tid,
                    'refid' => $refid,
                    'reply' => 'OK',
                ]);

            }

            if ($statusid === '02' || $statusid === 2) {
                if ($payment->isPending()) {
                    $payment->update([
                        'status' => 'failed',
                        'failure_details' => array_merge($payment->failure_details ?? [], [
                            'message' => $statusdesc ?: 'Payment failed',
                            'kpay_postback' => $data,
                        ]),
                        'last_attempted_at' => now(),
                    ]);
                }

                Log::info('KPay postback payment failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'statusdesc' => $statusdesc,
                ]);

                return response()->json([
                    'tid' => $tid,
                    'refid' => $refid,
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
