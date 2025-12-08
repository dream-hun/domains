<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Hosting\BillingCycle;
use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\SubscriptionAutoRenewalFailedNotification;
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
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event->data->object),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
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

    /**
     * Handle successful invoice payment (for subscriptions)
     */
    private function handleInvoicePaymentSucceeded(object $invoice): void
    {
        try {
            Log::info('Processing invoice.payment_succeeded', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription ?? null,
            ]);

            if (! $invoice->subscription) {
                Log::info('Invoice not associated with a subscription, skipping');

                return;
            }

            // Find subscription by Stripe subscription ID
            $subscription = Subscription::query()
                ->where('provider_resource_id', $invoice->subscription)
                ->first();

            if (! $subscription) {
                Log::warning('Subscription not found for Stripe subscription ID', [
                    'stripe_subscription_id' => $invoice->subscription,
                ]);

                return;
            }

            // Extend subscription period
            $billingCycle = $this->resolveBillingCycle($subscription->billing_cycle);
            $subscription->extendSubscription($billingCycle);
            $subscription->update(['last_renewal_attempt_at' => now()]);

            Log::info('Subscription extended successfully via Stripe webhook', [
                'subscription_id' => $subscription->id,
                'new_expiry' => $subscription->expires_at->toDateString(),
            ]);

        } catch (Exception $exception) {
            Log::error('Error handling invoice.payment_succeeded webhook', [
                'invoice_id' => $invoice->id ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed invoice payment (for subscriptions)
     */
    private function handleInvoicePaymentFailed(object $invoice): void
    {
        try {
            Log::warning('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription ?? null,
                'attempt_count' => $invoice->attempt_count ?? 0,
            ]);

            if (! $invoice->subscription) {
                return;
            }

            $subscription = Subscription::query()
                ->where('provider_resource_id', $invoice->subscription)
                ->first();

            if (! $subscription) {
                Log::warning('Subscription not found for failed invoice', [
                    'stripe_subscription_id' => $invoice->subscription,
                ]);

                return;
            }

            $subscription->update([
                'last_renewal_attempt_at' => now(),
            ]);

            // Notify user about failed renewal
            if ($subscription->user) {
                $failureReason = $invoice->last_payment_error->message ?? 'Payment method declined';
                $subscription->user->notify(
                    new SubscriptionAutoRenewalFailedNotification($subscription, $failureReason)
                );
            }

            Log::info('User notified about failed subscription renewal', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

        } catch (Exception $exception) {
            Log::error('Error handling invoice.payment_failed webhook', [
                'invoice_id' => $invoice->id ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Handle subscription deletion/cancellation
     */
    private function handleSubscriptionDeleted(object $stripeSubscription): void
    {
        try {
            Log::info('Processing customer.subscription.deleted', [
                'subscription_id' => $stripeSubscription->id,
            ]);

            $subscription = Subscription::query()
                ->where('provider_resource_id', $stripeSubscription->id)
                ->first();

            if (! $subscription) {
                Log::warning('Subscription not found for deleted Stripe subscription', [
                    'stripe_subscription_id' => $stripeSubscription->id,
                ]);

                return;
            }

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'auto_renew' => false,
            ]);

            Log::info('Subscription marked as cancelled', [
                'subscription_id' => $subscription->id,
            ]);

        } catch (Exception $exception) {
            Log::error('Error handling customer.subscription.deleted webhook', [
                'subscription_id' => $stripeSubscription->id ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveBillingCycle(string $cycle): BillingCycle
    {
        foreach (BillingCycle::cases() as $case) {
            if ($case->value === $cycle) {
                return $case;
            }
        }

        return BillingCycle::Monthly;
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
