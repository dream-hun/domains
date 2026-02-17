<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Enums\Hosting\BillingCycle;
use App\Models\Currency;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\SubscriptionAutoRenewalFailedNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Invoice;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

final class StripeWebhookController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Handle incoming Stripe webhooks
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.payment.stripe.webhook_secret');

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
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
                'charge.refunded' => $this->handleChargeRefunded($event->data->object),
                'charge.succeeded' => $this->handleChargeSucceeded($event->data->object),
                'charge.updated' => $this->handleChargeUpdated($event->data->object),
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
                // Try to find by session ID if available (from metadata or expanded session)
                if (isset($paymentIntent->metadata->session_id)) {
                    $payment = Payment::query()
                        ->where('stripe_session_id', $paymentIntent->metadata->session_id)
                        ->orderByDesc('attempt_number')
                        ->orderByDesc('id')
                        ->first();
                }

                // Last resort: Try to find session from Stripe API
                if (! $payment instanceof Payment) {
                    try {
                        $sessions = Session::all([
                            'payment_intent' => (string) $paymentIntent->id,
                            'limit' => 1,
                        ]);

                        if (! empty($sessions->data)) {
                            $session = $sessions->data[0];
                            $sessionId = $session->id;

                            // Try to find payment by session ID
                            $payment = Payment::query()
                                ->where('stripe_session_id', $sessionId)
                                ->orderByDesc('attempt_number')
                                ->orderByDesc('id')
                                ->first();

                            // If still not found, try to find via order
                            if (! $payment instanceof Payment) {
                                $order = Order::query()->where('stripe_session_id', $sessionId)->first();
                                if ($order) {
                                    $payment = $order->payments()
                                        ->where('payment_method', 'stripe')
                                        ->orderByDesc('attempt_number')
                                        ->orderByDesc('id')
                                        ->first();
                                }
                            }
                        }
                    } catch (Exception $e) {
                        Log::debug('Could not retrieve session from Stripe for payment intent', [
                            'payment_intent_id' => $paymentIntent->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if (! $payment instanceof Payment) {
                    Log::warning('Payment intent succeeded without matching payment record', [
                        'payment_intent_id' => $paymentIntent->id,
                        'metadata_payment_id' => $paymentIntent->metadata->payment_id ?? null,
                        'metadata_order_id' => $paymentIntent->metadata->order_id ?? null,
                        'metadata_session_id' => $paymentIntent->metadata->session_id ?? null,
                        'latest_charge' => $paymentIntent->latest_charge ?? null,
                        'charges_count' => is_array($paymentIntent->charges->data ?? null) ? count($paymentIntent->charges->data) : null,
                    ]);

                    return;
                }
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

            if ($order !== null && $order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending' ? 'processing' : $order->status,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'processed_at' => now(),
                ]);

                $primaryContact = $order->user->contacts()->where('is_primary', true)->first();
                $contactIds = [];
                if ($primaryContact) {
                    $contactIds = [
                        'registrant' => $primaryContact->id,
                        'admin' => $primaryContact->id,
                        'tech' => $primaryContact->id,
                        'billing' => $primaryContact->id,
                    ];
                }

                try {
                    $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                    $processOrderAction->handle($order, $contactIds, false);
                } catch (Exception $e) {
                    Log::warning('Order processing failed in webhook handler', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
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
                if ($payment->order !== null) {
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
                if ($payment->order !== null) {
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
     * Handle charge succeeded
     */
    private function handleChargeSucceeded(object $charge): void
    {
        try {
            Log::info('Charge succeeded', [
                'charge_id' => $charge->id,
                'amount' => $charge->amount ?? null,
                'currency' => $charge->currency ?? null,
            ]);

            // Find payment by charge ID
            $payment = Payment::query()->where('stripe_charge_id', $charge->id)->first();

            if (! $payment) {
                Log::info('Payment not found for charge succeeded', [
                    'charge_id' => $charge->id,
                    'payment_intent_id' => $charge->payment_intent ?? null,
                ]);

                return;
            }

            if (! $payment->isSuccessful()) {
                $payment->update([
                    'status' => 'succeeded',
                    'stripe_charge_id' => $charge->id,
                    'paid_at' => now(),
                    'last_attempted_at' => now(),
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'charge_status' => 'succeeded',
                        'charge_succeeded_at' => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('Payment marked as succeeded via charge.succeeded webhook', [
                    'payment_id' => $payment->id,
                    'charge_id' => $charge->id,
                ]);
            }

            // Update order status if exists and is pending
            $order = $payment->order;

            if ($order !== null && $order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending' ? 'processing' : $order->status,
                    'stripe_payment_intent_id' => $charge->payment_intent ?? $order->stripe_payment_intent_id,
                    'processed_at' => now(),
                ]);

                $primaryContact = $order->user->contacts()->where('is_primary', true)->first();
                $contactIds = [];
                if ($primaryContact) {
                    $contactIds = [
                        'registrant' => $primaryContact->id,
                        'admin' => $primaryContact->id,
                        'tech' => $primaryContact->id,
                        'billing' => $primaryContact->id,
                    ];
                }

                try {
                    $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                    $processOrderAction->handle($order, $contactIds, false);
                } catch (Exception $e) {
                    Log::warning('Order processing failed in charge.succeeded webhook handler', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('Order payment status updated via charge.succeeded webhook', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }

        } catch (Exception $exception) {
            Log::error('Error handling charge.succeeded webhook', [
                'charge_id' => $charge->id ?? 'unknown',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle charge updated
     */
    private function handleChargeUpdated(object $charge): void
    {
        try {
            Log::info('Charge updated', [
                'charge_id' => $charge->id,
                'status' => $charge->status ?? null,
                'amount_refunded' => $charge->amount_refunded ?? 0,
            ]);

            // Find payment by charge ID
            $payment = Payment::query()->where('stripe_charge_id', $charge->id)->first();

            if (! $payment) {
                Log::info('Payment not found for charge update', [
                    'charge_id' => $charge->id,
                ]);

                return;
            }

            $updateData = [];
            $metadataUpdates = array_merge($payment->metadata ?? [], [
                'charge_status' => $charge->status ?? null,
                'charge_updated_at' => now()->toDateTimeString(),
            ]);

            // Handle refunded status
            if (isset($charge->amount_refunded) && $charge->amount_refunded > 0) {
                if (! isset($metadataUpdates['refund_amount'])) {
                    $metadataUpdates['refund_amount'] = $charge->amount_refunded;
                    $metadataUpdates['refunded_at'] = now()->toDateTimeString();
                }

                if ($payment->status !== 'refunded') {
                    $updateData['status'] = 'refunded';
                }
            } else {
                // Map Stripe charge status to payment status
                $chargeStatus = $charge->status ?? null;
                $newPaymentStatus = match ($chargeStatus) {
                    'succeeded' => 'succeeded',
                    'pending' => 'pending',
                    'failed' => 'failed',
                    default => null,
                };

                if ($newPaymentStatus !== null && $payment->status !== $newPaymentStatus) {
                    $updateData['status'] = $newPaymentStatus;

                    if ($newPaymentStatus === 'succeeded' && ! $payment->paid_at) {
                        $updateData['paid_at'] = now();
                        $updateData['last_attempted_at'] = now();
                    }
                }
            }

            $updateData['metadata'] = $metadataUpdates;

            if (! empty($updateData)) {
                $payment->update($updateData);

                // Update order status if payment status changed and order exists
                if (isset($updateData['status']) && $payment->order !== null) {
                    $orderStatusUpdate = match ($updateData['status']) {
                        'succeeded' => ['payment_status' => 'paid', 'status' => $payment->order->status === 'pending' ? 'processing' : $payment->order->status],
                        'failed' => ['payment_status' => 'failed', 'status' => 'failed'],
                        'refunded' => ['payment_status' => 'refunded', 'status' => 'refunded'],
                        default => [],
                    };

                    if (! empty($orderStatusUpdate)) {
                        $payment->order->update($orderStatusUpdate);
                    }
                }

                Log::info('Payment updated via charge.updated webhook', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order->id ?? null,
                    'new_status' => $updateData['status'] ?? $payment->status,
                ]);
            }

        } catch (Exception $exception) {
            Log::error('Error handling charge.updated webhook', [
                'charge_id' => $charge->id ?? 'unknown',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
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

            $billingCycleValue = $invoice->metadata->billing_cycle ?? $subscription->billing_cycle;
            $billingCycle = $this->resolveBillingCycle($billingCycleValue);

            $paidAmount = isset($invoice->amount_paid) ? ($invoice->amount_paid / 100) : null;

            if ($paidAmount === null && isset($invoice->total)) {
                $paidAmount = $invoice->total / 100;
            }

            $planPrice = HostingPlanPrice::query()
                ->with('currency')
                ->where('hosting_plan_id', $subscription->hosting_plan_id)
                ->where('billing_cycle', $billingCycle->value)
                ->where('status', 'active')
                ->first();

            if (! $planPrice) {
                Log::error('No active pricing found for subscription renewal', [
                    'subscription_id' => $subscription->id,
                    'billing_cycle' => $billingCycle->value,
                ]);
                throw new Exception('No active pricing found for billing cycle '.$billingCycle->value);
            }

            $order = $this->createSubscriptionRenewalOrder(
                $subscription,
                $planPrice,
                $billingCycle,
                $paidAmount,
                $invoice->id
            );

            $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
            $processOrderAction->handle($order, [], false);

            Log::info('Subscription renewal order created and job dispatched via Stripe webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'subscription_id' => $subscription->id,
                'billing_cycle' => $billingCycle->value,
                'paid_amount' => $paidAmount,
            ]);

        } catch (Exception $exception) {
            Log::error('Error handling invoice.payment_succeeded webhook', [
                'invoice_id' => $invoice->id ?? 'unknown',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        } catch (Throwable $e) {
            $e->getMessage();
        }
    }

    /**
     * Create an order for automatic subscription renewal
     *
     * @throws Throwable
     */
    private function createSubscriptionRenewalOrder(
        Subscription $subscription,
        HostingPlanPrice $planPrice,
        BillingCycle $billingCycle,
        ?float $paidAmount,
        string $stripeInvoiceId
    ): Order {
        $user = $subscription->user;

        throw_unless($user, Exception::class, 'Subscription has no associated user');

        throw_unless(Currency::getActiveCurrencies()->firstWhere('code', 'USD'), \Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'type' => 'subscription_renewal',
            'status' => 'paid',
            'payment_method' => 'stripe',
            'payment_status' => 'paid',
            'total_amount' => $paidAmount ?? $planPrice->getPriceInBaseCurrency('renewal_price'),
            'subtotal' => $paidAmount ?? $planPrice->getPriceInBaseCurrency('renewal_price'),
            'tax' => 0,
            'currency' => 'USD',
            'billing_email' => $user->email,
            'billing_name' => $user->name,
            'billing_address' => [],
            'stripe_payment_intent_id' => $stripeInvoiceId,
            'processed_at' => now(),
            'items' => [
                [
                    'id' => $subscription->id,
                    'name' => ($subscription->domain ?: 'Hosting').' - '.$subscription->plan->name.' (Auto-Renewal)',
                    'price' => $paidAmount ?? $planPrice->getPriceInBaseCurrency('renewal_price'),
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'subscription_renewal',
                        'subscription_id' => $subscription->id,
                        'subscription_uuid' => $subscription->uuid,
                        'billing_cycle' => $billingCycle->value,
                        'hosting_plan_id' => $subscription->hosting_plan_id,
                        'hosting_plan_pricing_id' => $planPrice->id,
                        'domain' => $subscription->domain,
                        'currency' => 'USD',
                    ],
                ],
            ],
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'domain_name' => $subscription->domain ?: 'Hosting',
            'domain_type' => 'subscription_renewal',
            'price' => $paidAmount ?? $planPrice->getPriceInBaseCurrency('renewal_price'),
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'quantity' => 1,
            'years' => 1,
            'total_amount' => $paidAmount ?? $planPrice->getPriceInBaseCurrency('renewal_price'),
            'metadata' => [
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'billing_cycle' => $billingCycle->value,
                'hosting_plan_id' => $subscription->hosting_plan_id,
                'hosting_plan_pricing_id' => $planPrice->id,
                'stripe_invoice_id' => $stripeInvoiceId,
                'auto_renewal' => true,
            ],
        ]);

        return $order->fresh(['orderItems']);
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

    /**
     * Handle checkout session completed webhook
     */
    private function handleCheckoutSessionCompleted(object $session): void
    {
        try {
            Log::info('Processing checkout.session.completed', [
                'session_id' => $session->id,
                'payment_intent_id' => $session->payment_intent ?? null,
                'metadata' => (array) ($session->metadata ?? []),
            ]);

            // Method 1: Find by stripe_session_id (most direct)
            $order = Order::query()->where('stripe_session_id', $session->id)->first();

            // Method 2: Find by metadata order_id
            if (! $order && isset($session->metadata->order_id)) {
                $order = Order::query()->find($session->metadata->order_id);
            }

            // Method 3: Find by payment intent via Payment model
            if (! $order && isset($session->payment_intent)) {
                $payment = Payment::query()
                    ->where('stripe_payment_intent_id', $session->payment_intent)
                    ->orWhere('stripe_session_id', $session->id)
                    ->first();
                $order = $payment?->order;
            }

            // Method 4: Find by payment intent directly on order
            if (! $order && isset($session->payment_intent)) {
                $order = Order::query()
                    ->where('stripe_payment_intent_id', $session->payment_intent)
                    ->first();
            }

            if (! $order) {
                Log::error('Order not found for checkout session', [
                    'session_id' => $session->id,
                    'payment_intent_id' => $session->payment_intent ?? null,
                    'metadata_order_id' => $session->metadata->order_id ?? null,
                    'metadata_payment_id' => $session->metadata->payment_id ?? null,
                ]);

                return;
            }

            Log::info('Order found for checkout session', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'session_id' => $session->id,
            ]);

            // Update order with Stripe information
            $updateData = [];
            $shouldUpdatePaymentStatus = false;

            // If payment is pending, mark it as paid
            if ($order->payment_status === 'pending') {
                $updateData['payment_status'] = 'paid';
                $updateData['status'] = $order->status === 'pending' ? 'processing' : $order->status;
                $updateData['processed_at'] = now();
                $shouldUpdatePaymentStatus = true;
            }

            // Ensure stripe_session_id is set (even if already paid)
            if (! $order->stripe_session_id) {
                $updateData['stripe_session_id'] = $session->id;
            }

            // Update payment intent ID if available and not already set
            if (isset($session->payment_intent) && ! $order->stripe_payment_intent_id) {
                $updateData['stripe_payment_intent_id'] = $session->payment_intent;
            }

            // Update order if there are changes
            if (! empty($updateData)) {
                $order->update($updateData);
            }

            // Update associated payment record if it exists (always check, even if order wasn't updated)
            $payment = null;
            if (isset($session->metadata->payment_id)) {
                $payment = Payment::query()->find($session->metadata->payment_id);
            }

            if (! $payment && isset($session->payment_intent)) {
                $payment = Payment::query()
                    ->where('stripe_payment_intent_id', $session->payment_intent)
                    ->orWhere('stripe_session_id', $session->id)
                    ->first();
            }

            if (! $payment) {
                $payment = $order->payments()
                    ->where('payment_method', 'stripe')
                    ->orderByDesc('attempt_number')
                    ->orderByDesc('id')
                    ->first();
            }

            if ($payment) {
                $paymentUpdateData = [];

                // Update payment status if it's not successful
                if (! $payment->isSuccessful() && $shouldUpdatePaymentStatus) {
                    $paymentUpdateData['status'] = 'succeeded';
                    $paymentUpdateData['paid_at'] = now();
                    $paymentUpdateData['last_attempted_at'] = now();
                }

                // Ensure stripe_session_id is set (even if already successful)
                if (! $payment->stripe_session_id) {
                    $paymentUpdateData['stripe_session_id'] = $session->id;
                }

                // Update payment intent ID if available (always update to ensure we have the real Stripe ID)
                if (isset($session->payment_intent)) {
                    // Check if current payment intent ID is a placeholder (starts with "pending-")
                    $currentIntentId = $payment->stripe_payment_intent_id;
                    $isPlaceholder = $currentIntentId && str_starts_with((string) $currentIntentId, 'pending-');

                    // Always update if it's a placeholder or if not set
                    if ($isPlaceholder || ! $currentIntentId) {
                        $paymentUpdateData['stripe_payment_intent_id'] = $session->payment_intent;
                    }
                }

                if ($paymentUpdateData !== []) {
                    $payment->update($paymentUpdateData);

                    Log::info('Payment record updated via webhook', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'status' => $paymentUpdateData['status'] ?? $payment->status,
                        'payment_intent_updated' => isset($paymentUpdateData['stripe_payment_intent_id']),
                    ]);
                }
            }

            Log::info('Order payment status updated via webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'stripe_session_id' => $order->stripe_session_id,
                'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
                'payment_updated' => $payment !== null,
                'payment_status_changed' => $shouldUpdatePaymentStatus,
            ]);

            $primaryContact = $order->user->contacts()->where('is_primary', true)->first();
            $contactIds = [];
            if ($primaryContact) {
                $contactIds = [
                    'registrant' => $primaryContact->id,
                    'admin' => $primaryContact->id,
                    'tech' => $primaryContact->id,
                    'billing' => $primaryContact->id,
                ];
            }

            try {
                $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                $processOrderAction->handle($order, $contactIds, false);
            } catch (Exception $e) {
                Log::warning('Order processing failed in checkout session webhook handler', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

        } catch (Exception $exception) {
            Log::error('Error handling checkout.session.completed webhook', [
                'session_id' => $session->id ?? 'unknown',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
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

        // Try to find by exact payment intent ID match
        $payment = Payment::query()->where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if ($payment) {
            return $payment;
        }

        // Try to find by order's payment intent ID and get the latest payment attempt
        $metadataOrderId = $paymentIntent->metadata->order_id ?? null;
        if ($metadataOrderId) {
            $order = Order::query()->find($metadataOrderId);
            if ($order) {
                $payment = $order->payments()
                    ->where('payment_method', 'stripe')
                    ->orderByDesc('attempt_number')
                    ->orderByDesc('id')
                    ->first();
                if ($payment) {
                    return $payment;
                }
            }
        }

        // Try to find order by payment intent ID
        $order = Order::query()->where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if ($order) {
            $payment = $order->payments()
                ->where('payment_method', 'stripe')
                ->orderByDesc('attempt_number')
                ->orderByDesc('id')
                ->first();
            if ($payment) {
                return $payment;
            }
        }

        // Try to find by charge ID if payment intent has charges
        if (isset($paymentIntent->latest_charge)) {
            $chargeId = is_string($paymentIntent->latest_charge)
                ? $paymentIntent->latest_charge
                : ($paymentIntent->latest_charge->id ?? null);

            if ($chargeId) {
                $payment = Payment::query()->where('stripe_charge_id', $chargeId)->first();
                if ($payment) {
                    return $payment;
                }
            }
        }

        // Try to find by session ID from payment intent metadata or by looking up the session
        $sessionId = $paymentIntent->metadata->session_id ?? null;
        if (! $sessionId) {
            // Try to get session ID from the payment intent's invoice or checkout session
            // Payment intents created by checkout sessions have an 'invoice' field
            // We can also try to retrieve the session that created this payment intent
            try {
                if (isset($paymentIntent->invoice)) {
                    $invoiceId = is_string($paymentIntent->invoice)
                        ? $paymentIntent->invoice
                        : ($paymentIntent->invoice->id ?? null);
                    if ($invoiceId) {
                        $invoice = Invoice::retrieve($invoiceId);
                        $sessionId = $invoice->subscription ?? null;
                    }
                }
            } catch (Exception $e) {
                Log::debug('Could not retrieve invoice for payment intent', [
                    'payment_intent_id' => $paymentIntent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sessionId) {
            $payment = Payment::query()
                ->where('stripe_session_id', $sessionId)
                ->where('payment_method', 'stripe')
                ->orderByDesc('attempt_number')
                ->orderByDesc('id')
                ->first();
            if ($payment) {
                return $payment;
            }

            // Also try to find via order's session ID
            $order = Order::query()->where('stripe_session_id', $sessionId)->first();
            if ($order) {
                $payment = $order->payments()
                    ->where('payment_method', 'stripe')
                    ->orderByDesc('attempt_number')
                    ->orderByDesc('id')
                    ->first();
                if ($payment) {
                    return $payment;
                }
            }
        }

        return null;
    }
}
