<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Order;
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

            // Check if payment already exists
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if ($payment) {
                Log::info('Payment already processed', [
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_id' => $payment->id,
                ]);

                // If payment exists but order is still processing, dispatch the renewal job again
                $order = $payment->order;
                if ($order && $order->status === 'processing') {
                    Log::info('Re-dispatching renewal job for existing order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ]);
                    ProcessDomainRenewalJob::dispatch($order);
                }

                return;
            }

            // Payment doesn't exist yet - this means webhook arrived before user returned to success page
            // The payment and order will be created when user returns to success page
            Log::info('Payment intent succeeded but payment record not yet created - will be created on success page', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

        } catch (Exception $e) {
            Log::error('Error handling payment_intent.succeeded webhook', [
                'payment_intent_id' => $paymentIntent->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

            // Update payment record if it exists
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                    ]),
                ]);

                // Update order status if exists
                if ($payment->order) {
                    $payment->order->update([
                        'status' => 'failed',
                        'payment_status' => 'failed',
                    ]);
                }
            }

        } catch (Exception $e) {
            Log::error('Error handling payment_intent.payment_failed webhook', [
                'payment_intent_id' => $paymentIntent->id ?? 'unknown',
                'error' => $e->getMessage(),
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
            $payment = Payment::where('stripe_charge_id', $charge->id)->first();

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

        } catch (Exception $e) {
            Log::error('Error handling charge.refunded webhook', [
                'charge_id' => $charge->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
