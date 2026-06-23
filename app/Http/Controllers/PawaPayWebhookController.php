<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Models\Order;
use App\Models\Payment;
use App\Services\IdempotencyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PawaPayWebhookController extends Controller
{
    public function __construct(private readonly IdempotencyService $idempotency) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            if (! $this->verifySignature($request)) {
                Log::warning('PawaPay webhook: invalid signature', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = $request->json()->all();
            $depositId = $data['depositId'] ?? null;
            $status = $data['status'] ?? null;

            Log::info('PawaPay webhook received', [
                'depositId' => $depositId,
                'status' => $status,
            ]);

            if (! $depositId) {
                return response()->json(['error' => 'Missing depositId'], 400);
            }

            $payment = Payment::query()
                ->where('pawapay_deposit_id', $depositId)
                ->first();

            if (! $payment instanceof Payment) {
                Log::warning('PawaPay webhook: payment not found', ['depositId' => $depositId]);

                return response()->json(['error' => 'Payment not found'], 404);
            }

            $order = $payment->order;

            if (! $order) {
                Log::error('PawaPay webhook: order not found for payment', ['payment_id' => $payment->id]);

                return response()->json(['error' => 'Order not found'], 404);
            }

            $idempotencyKey = 'pawapay:'.$depositId.':'.$status;

            $this->idempotency->once($idempotencyKey, function () use ($payment, $order, $data, $status, $depositId): void {
                match ($status) {
                    'COMPLETED' => $this->handleCompleted($payment, $order, $data),
                    'FAILED', 'TIMED_OUT' => $this->handleFailed($payment, $order, $data, (string) $status),
                    'DUPLICATE_IGNORED' => Log::info('PawaPay webhook: duplicate ignored', ['depositId' => $depositId]),
                    default => Log::info('PawaPay webhook: unhandled status', ['status' => $status, 'depositId' => $depositId]),
                };
            });

            return response()->json(['status' => 'ok']);

        } catch (Exception $exception) {
            Log::error('PawaPay webhook processing error', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.payment.pawapay.webhook_secret');

        if (! $secret) {
            Log::error('PawaPay webhook secret not configured — rejecting request');

            return false;
        }

        $signature = $request->header('X-PawaPay-Signature') ?? $request->header('Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, $signature);
    }

    private function handleCompleted(Payment $payment, Order $order, array $data): void
    {
        DB::transaction(function () use ($payment, &$order, $data): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status === 'paid') {
                return;
            }

            $payment->update([
                'status' => 'succeeded',
                'paid_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'pawapay_webhook_data' => $data,
                    'pawapay_completed_at' => now()->toDateTimeString(),
                ]),
                'last_attempted_at' => now(),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);
        });

        $payment->refresh();
        $order->refresh();

        try {
            $contactIds = [];
            $metadata = $order->metadata ?? [];

            if (isset($metadata['contact_ids'])) {
                $contactIds = $metadata['contact_ids'];
            } elseif (isset($metadata['selected_contact_id'])) {
                $id = $metadata['selected_contact_id'];
                $contactIds = ['registrant' => $id, 'admin' => $id, 'tech' => $id, 'billing' => $id];
            }

            $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
            $processOrderAction->handle($order, $contactIds, false);

            Log::info('PawaPay webhook: order processed successfully', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
            ]);
        } catch (Exception $exception) {
            Log::error('PawaPay webhook: order processing failed', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function handleFailed(Payment $payment, Order $order, array $data, string $status): void
    {
        DB::transaction(function () use ($payment, $order, $data, $status): void {
            $payment->update([
                'status' => 'failed',
                'failure_details' => array_merge($payment->failure_details ?? [], [
                    'message' => 'PawaPay deposit '.$status,
                    'pawapay_status' => $status,
                ]),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'pawapay_webhook_data' => $data,
                ]),
                'last_attempted_at' => now(),
            ]);

            $order->update([
                'payment_status' => 'failed',
                'status' => 'failed',
            ]);
        });

        Log::info('PawaPay webhook: deposit failed/timed-out', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'status' => $status,
        ]);
    }
}
