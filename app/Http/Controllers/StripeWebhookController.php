<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

final class StripeWebhookController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Handle incoming Stripe webhooks
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::error('Stripe webhook secret not configured');

            return response('Webhook secret not configured', 500);
        }

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            // Handle the event
            match ($event->type) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
                'charge.refunded' => $this->handleChargeRefunded($event->data->object),
                default => Log::info('Unhandled webhook event type', ['type' => $event->type]),
            };

            return response('Webhook handled', 200);

        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);

        } catch (Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Webhook processing failed', 500);
        }
    }

    /**
     * Handle successful payment intent
     */
    private function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        try {
            Log::info('Processing payment_intent.succeeded', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ]);

            $payment = $this->findPaymentForIntent($paymentIntent);

            if (! $payment instanceof Payment) {
                Log::info('Payment intent succeeded without matching payment record', [
                    'payment_intent_id' => $paymentIntent->id,
                    'metadata_payment_id' => $paymentIntent->metadata->payment_id ?? null,
                    'metadata_order_id' => $paymentIntent->metadata->order_id ?? null,
                ]);

                return;
            }

            if (! $payment->isSuccessful()) {
                $payment->update([
                    'status' => 'succeeded',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'paid_at' => now(),
                    'last_attempted_at' => now(),
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'stripe_payment_status' => $paymentIntent->status ?? 'succeeded',
                    ]),
                ]);
            }

            $order = $payment->order;

            if ($order && $order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending' ? 'processing' : $order->status,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'processed_at' => now(),
                ]);

                if ($order->type === 'renewal') {
                    dispatch(new ProcessDomainRenewalJob($order));
                }
            }

        } catch (Exception $exception) {
            Log::error('Error handling payment_intent.succeeded webhook', [
                'payment_intent_id' => $paymentIntent->id ?? 'unknown',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle failed payment intent
     */
    private function handlePaymentIntentFailed(object $paymentIntent): void
    {
        try {
            Log::warning('Payment intent failed', [
                'payment_intent_id' => $paymentIntent->id,
                'failure_message' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
            ]);

            $payment = $this->findPaymentForIntent($paymentIntent);

            if ($payment instanceof Payment) {
                $failureDetails = array_filter([
                    'message' => $paymentIntent->last_payment_error->message ?? null,
                    'code' => $paymentIntent->last_payment_error->code ?? null,
                ], static fn ($value): bool => $value !== null);

                $payment->update([
                    'status' => 'failed',
                    'failure_details' => array_merge($payment->failure_details ?? [], $failureDetails),
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'stripe_payment_status' => $paymentIntent->status ?? 'failed',
                    ]),
                    'last_attempted_at' => now(),
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id ?: $paymentIntent->id,
                ]);

                // Update order status if exists
                if ($payment->order) {
                    $payment->order->update([
                        'status' => 'failed',
                        'payment_status' => 'failed',
                    ]);
                }
            }

        } catch (Exception $exception) {
            Log::error('Error handling payment_intent.payment_failed webhook', [
                'payment_intent_id' => $paymentIntent->id ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Handle charge refunded
     */
    private function handleChargeRefunded(object $charge): void
    {
        try {
            Log::info('Charge refunded', [
                'charge_id' => $charge->id,
                'amount_refunded' => $charge->amount_refunded,
            ]);

            // Find payment by charge ID
            $payment = Payment::query()->where('stripe_charge_id', $charge->id)->first();

            if ($payment) {
                $payment->update([
                    'status' => 'refunded',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'refund_amount' => $charge->amount_refunded,
                        'refunded_at' => now()->toDateTimeString(),
                    ]),
                ]);

                // Update order status if exists
                if ($payment->order) {
                    $payment->order->update([
                        'status' => 'refunded',
                        'payment_status' => 'refunded',
                    ]);
                }

                Log::info('Payment marked as refunded', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order->id ?? null,
                ]);
            }

        } catch (Exception $exception) {
            Log::error('Error handling charge.refunded webhook', [
                'charge_id' => $charge->id ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function findPaymentForIntent(object $paymentIntent): ?Payment
    {
        $metadataPaymentId = $paymentIntent->metadata->payment_id ?? null;

        if ($metadataPaymentId) {
            $payment = Payment::query()->find($metadataPaymentId);
            if ($payment) {
                return $payment;
            }
        }

        return Payment::query()->where('stripe_payment_intent_id', $paymentIntent->id)->first();
    }
}
