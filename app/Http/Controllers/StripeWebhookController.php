<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.payment.stripe.webhook_secret');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (Exception $e) {
            Log::error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook error'], 400);
        }

        // Handle the event
        try {
            match ($event->type) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
                default => Log::info('Unhandled Stripe webhook event', ['type' => $event->type]),
            };

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    private function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if (! $orderId) {
            Log::warning('Payment intent succeeded but no order_id in metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $order = Order::find($orderId);

        if (! $order) {
            Log::error('Order not found for payment intent', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Check idempotency - if already processed, skip
        if ($order->isPaid()) {
            Log::info('Payment intent already processed', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Update order payment status
        $order->update([
            'payment_status' => 'paid',
            'processed_at' => now(),
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        // Trigger domain registration if not already processing
        if ($order->status === 'pending') {
            // Use the user's primary contact for domain registration (all roles)
            $contactId = $order->user->contacts()->where('is_primary', true)->first()?->id;

            if ($contactId) {
                $contactIds = [
                    'registrant' => $contactId,
                    'admin' => $contactId,
                    'tech' => $contactId,
                    'billing' => $contactId,
                ];
                $this->orderService->processDomainRegistrations($order, $contactIds);
            }

            $this->orderService->sendOrderConfirmation($order);
        }

        Log::info('Payment intent succeeded webhook processed', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntent->id,
        ]);
    }

    private function handlePaymentIntentFailed(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if (! $orderId) {
            Log::warning('Payment intent failed but no order_id in metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $order = Order::find($orderId);

        if (! $order) {
            Log::error('Order not found for failed payment intent', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Update order payment status
        $order->update([
            'payment_status' => 'failed',
            'notes' => 'Payment failed: '.($paymentIntent->last_payment_error->message ?? 'Unknown error'),
        ]);

        Log::info('Payment intent failed webhook processed', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
        ]);
    }
}
