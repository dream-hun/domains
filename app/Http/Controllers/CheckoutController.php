<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\StripeHelper;
use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\TransactionLogger;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly TransactionLogger $transactionLogger
    ) {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Show checkout page - immediately create session and redirect to Stripe
     */
    public function index(): RedirectResponse
    {
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return to_route('dashboard')
                ->with('error', 'Your cart is empty.');
        }

        try {
            // Create order first
            $orderNumber = $this->generateOrderNumber();
            $cartTotal = Cart::getTotal();
            $currency = $cartItems->first()->attributes['currency'] ?? 'USD';
            $user = auth()->user();

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'type' => 'renewal',
                'subtotal' => $cartTotal,
                'tax' => 0,
                'total_amount' => $cartTotal,
                'currency' => $currency,
                'status' => 'pending',
                'payment_status' => 'pending',
                'billing_email' => $user->email,
                'billing_name' => $user->first_name.' '.$user->last_name,
                'items' => $cartItems->toArray(),
            ]);

            $paymentAttempt = $this->createPaymentAttempt($order);

            // Create Stripe Checkout Session
            $session = $this->createStripeCheckoutSession($order, $cartItems, $paymentAttempt);

            // Store session ID in order
            $order->update(['stripe_session_id' => $session->id]);

            // Redirect to Stripe Checkout (external URL)
            return redirect()->away($session->url);

        } catch (Exception $exception) {
            $this->failPaymentAttempt($paymentAttempt, $exception->getMessage());
            $this->transactionLogger->logFailure(
                order: $order,
                method: 'stripe',
                error: 'Failed to create checkout session',
                details: $exception->getMessage(),
                payment: $paymentAttempt
            );
            Log::error('Failed to create checkout session', [
                'error' => $exception->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return to_route('cart.index')
                ->with('error', 'Failed to initialize checkout. Please try again.');
        }
    }

    /**
     * Handle successful payment from Stripe
     */
    public function success(Request $request, string $order): View|RedirectResponse
    {
        try {
            $sessionId = $request->query('session_id');

            if (! $sessionId) {
                return to_route('dashboard')
                    ->with('error', 'Invalid session.');
            }

            // Retrieve the order
            $order = Order::query()->where('order_number', $order)->firstOrFail();

            // Verify order belongs to current user
            abort_if($order->user_id !== auth()->id(), 403);

            $session = Session::retrieve($sessionId);

            $paymentAttempt = $order->payments()
                ->where(function ($query) use ($sessionId, $session): void {
                    $query->where('stripe_session_id', $sessionId);

                    if (! empty($session->payment_intent)) {
                        $query->orWhere('stripe_payment_intent_id', $session->payment_intent);
                    }
                })
                ->orderByDesc('attempt_number')
                ->orderByDesc('id')
                ->first();

            if (! $paymentAttempt) {
                Log::warning('Checkout success without matching payment attempt', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'session_id' => $sessionId,
                    'payment_intent' => $session->payment_intent,
                ]);
            }

            if ($session->payment_status !== 'paid') {
                $this->markPaymentAttemptFailed($order, $sessionId, $session->last_payment_error->message ?? 'Payment was not successful.');

                return to_route('checkout.cancel')
                    ->with('error', 'Payment was not successful.');
            }

            DB::beginTransaction();

            try {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'processed_at' => now(),
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);

                if ($paymentAttempt && ! $paymentAttempt->isSuccessful()) {
                    $paymentAttempt->update([
                        'status' => 'succeeded',
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'paid_at' => now(),
                        'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                            'stripe_payment_status' => $session->payment_status,
                        ]),
                        'last_attempted_at' => now(),
                    ]);
                }

                DB::commit();

                Cart::clear();

                // Create OrderItem records from order's items JSON if they don't exist
                $this->createOrderItemsFromJson($order);

                // Dispatch appropriate renewal jobs based on order items
                $this->dispatchRenewalJobs($order);

                $order->refresh();
                $paymentAttempt?->refresh();

                $this->transactionLogger->logSuccess(
                    order: $order,
                    method: 'stripe',
                    transactionId: (string) $session->payment_intent,
                    amount: (float) ($paymentAttempt->amount ?? $order->total_amount),
                    payment: $paymentAttempt
                );

                Log::info('Renewal order completed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ]);

                return view('checkout.success', ['order' => $order]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $exception) {
            Log::error('Payment success handling failed', [
                'error' => $exception->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->markPaymentAttemptFailed(
                $order,
                (string) $request->query('session_id', ''),
                $exception->getMessage()
            );

            return to_route('dashboard')
                ->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle payment cancellation
     */
    public function cancel(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('order');

        if ($orderNumber) {
            try {
                $order = Order::query()->where('order_number', $orderNumber)->first();

                if ($order && $order->user_id === auth()->id()) {
                    $order->update([
                        'payment_status' => 'cancelled',
                        'status' => 'cancelled',
                        'notes' => 'Payment cancelled by user',
                    ]);

                    $pendingPayment = $order->payments()
                        ->where('status', 'pending')
                        ->orderByDesc('attempt_number')
                        ->orderByDesc('id')
                        ->first();

                    if ($pendingPayment) {
                        $pendingPayment->update([
                            'status' => 'cancelled',
                            'failure_details' => array_merge($pendingPayment->failure_details ?? [], [
                                'message' => 'Payment cancelled by user',
                            ]),
                            'last_attempted_at' => now(),
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error('Failed to update cancelled order', [
                    'order_number' => $orderNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('checkout.cancel');
    }

    /**
     * Redirect user to existing Stripe checkout session.
     */
    public function stripeRedirect(string $orderNumber): RedirectResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();

        abort_if($order->user_id !== auth()->id(), 403);

        if ($order->stripe_session_id) {
            $session = Session::retrieve($order->stripe_session_id);

            return redirect()->away($session->url);
        }

        Log::warning('Stripe redirect attempted without session', [
            'order_number' => $orderNumber,
            'user_id' => auth()->id(),
        ]);

        return to_route('checkout.index')->with('error', 'Unable to locate the payment session. Please restart checkout.');
    }

    /**
     * Stripe success callback wrapper to retain legacy route.
     */
    public function stripeSuccess(Request $request, string $orderNumber): View|RedirectResponse|ViewContract
    {
        return $this->success($request, $orderNumber);
    }

    /**
     * Stripe cancel callback wrapper to retain legacy route.
     */
    public function stripeCancel(Request $request, string $orderNumber): View|RedirectResponse
    {
        $request->query->set('order', $orderNumber);

        return $this->cancel($request);
    }

    /**
     * Create Stripe Checkout Session
     *
     * @throws ApiErrorException
     */
    private function createStripeCheckoutSession(Order $order, $cartItems, Payment $paymentAttempt): Session
    {
        $user = $order->user;

        // Create or get Stripe customer
        if (! $user->stripe_id) {
            $customer = Customer::create([
                'name' => $user->name,
                'email' => $user->email,
                'metadata' => [
                    'user_id' => (string) $user->id,
                ],
            ]);

            $user->update(['stripe_id' => $customer->id]);
        }

        // Build line items from cart
        $lineItems = [];
        foreach ($cartItems as $item) {
            $itemPrice = $item->getPriceSum();
            $stripeAmount = StripeHelper::convertToStripeAmount(
                $itemPrice,
                $order->currency
            );

            $displayName = $this->getItemDisplayName($item);
            $period = $this->getItemPeriod($item);

            $lineItems[] = [
                'price_data' => [
                    'currency' => mb_strtolower($order->currency),
                    'product_data' => [
                        'name' => $displayName,
                        'description' => $period,
                    ],
                    'unit_amount' => $stripeAmount,
                ],
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success', ['order' => $order->order_number]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel').'?order='.$order->order_number,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'user_id' => (string) $user->id,
                'order_type' => 'renewal',
                'payment_id' => (string) $paymentAttempt->id,
            ],
        ]);

        $paymentAttempt->update([
            'stripe_session_id' => $session->id,
            'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                'stripe_checkout_url' => $session->url,
                'stripe_payment_status' => $session->payment_status ?? 'open',
            ]),
            'last_attempted_at' => now(),
        ]);

        return $session;
    }

    /**
     * Generate a unique order number
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-'.mb_strtoupper(Str::random(10));
    }

    private function createPaymentAttempt(Order $order): Payment
    {
        $nextAttempt = (int) ($order->payments()->max('attempt_number') ?? 0) + 1;

        return $order->payments()->create([
            'user_id' => $order->user_id,
            'status' => 'pending',
            'payment_method' => 'stripe',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'metadata' => [
                'order_type' => $order->type,
            ],
            'attempt_number' => $nextAttempt,
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, $nextAttempt),
            'last_attempted_at' => now(),
        ]);
    }

    private function failPaymentAttempt(Payment $payment, string $message): void
    {
        $payment->update([
            'status' => 'failed',
            'failure_details' => array_merge($payment->failure_details ?? [], [
                'message' => $message,
            ]),
            'last_attempted_at' => now(),
        ]);
    }

    private function markPaymentAttemptFailed(Order $order, string $sessionId, ?string $message = null): void
    {
        if ($sessionId === '') {
            return;
        }

        $paymentAttempt = $order->payments()
            ->where('stripe_session_id', $sessionId)
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();

        if (! $paymentAttempt || $paymentAttempt->isSuccessful() || $paymentAttempt->status === 'failed') {
            return;
        }

        $failureDetails = $paymentAttempt->failure_details ?? [];

        if ($message) {
            $failureDetails['message'] = $message;
        }

        $paymentAttempt->update([
            'status' => 'failed',
            'failure_details' => $failureDetails,
            'last_attempted_at' => now(),
        ]);
    }

    /**
     * Create OrderItem records from order's items JSON field
     * This ensures OrderItem records exist for SubscriptionRenewalService to process
     */
    private function createOrderItemsFromJson(Order $order): void
    {
        // Check if OrderItem records already exist to avoid duplicates
        if ($order->orderItems()->exists()) {
            Log::info('OrderItem records already exist for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        $items = $order->items ?? [];

        if (empty($items)) {
            Log::warning('No items found in order JSON to create OrderItem records', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        foreach ($items as $item) {
            $attributes = $item['attributes'] ?? [];
            $itemType = $attributes['type'] ?? 'registration';
            $itemPrice = (float) ($item['price'] ?? 0);
            $itemQuantity = (int) ($item['quantity'] ?? 1);
            $itemTotal = $itemPrice * $itemQuantity;
            $itemCurrency = $attributes['currency'] ?? $order->currency ?? 'USD';
            $domainId = $attributes['domain_id'] ?? null;
            $domainName = $attributes['domain_name'] ?? $item['name'] ?? 'Unknown';

            // Get the exchange rate for the item's currency
            $itemCurrencyModel = Currency::query()->where('code', $itemCurrency)->first();
            $exchangeRate = $itemCurrencyModel ? $itemCurrencyModel->exchange_rate : 1.0;

            // Build metadata from attributes
            $itemMetadata = $attributes['metadata'] ?? [];

            // For subscription renewals, ensure subscription_id and billing_cycle are in metadata
            if ($itemType === 'subscription_renewal') {
                $subscriptionId = $attributes['subscription_id'] ?? null;
                if ($subscriptionId) {
                    $itemMetadata['subscription_id'] = $subscriptionId;
                }

                // CRITICAL: Ensure billing_cycle is stored in metadata
                $billingCycle = $attributes['billing_cycle'] ?? null;
                if ($billingCycle) {
                    $itemMetadata['billing_cycle'] = $billingCycle;
                } else {
                    Log::warning('Billing cycle not found in subscription renewal attributes', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'item_name' => $domainName,
                        'attributes' => $attributes,
                    ]);
                }

                // Also include other subscription-related attributes
                if (isset($attributes['subscription_uuid'])) {
                    $itemMetadata['subscription_uuid'] = $attributes['subscription_uuid'];
                }
                if (isset($attributes['hosting_plan_id'])) {
                    $itemMetadata['hosting_plan_id'] = $attributes['hosting_plan_id'];
                }
                if (isset($attributes['hosting_plan_price_id'])) {
                    $itemMetadata['hosting_plan_price_id'] = $attributes['hosting_plan_price_id'];
                }
            }

            // For domain renewals, include years in metadata if present
            if ($itemType === 'renewal' && isset($attributes['years'])) {
                $itemMetadata['years'] = $attributes['years'];
            }

            OrderItem::query()->create([
                'order_id' => $order->id,
                'domain_name' => $domainName,
                'domain_type' => $itemType,
                'domain_id' => $domainId,
                'price' => $itemPrice,
                'currency' => $itemCurrency,
                'exchange_rate' => $exchangeRate,
                'quantity' => $itemQuantity,
                'years' => $itemQuantity, // For renewals, quantity typically represents years
                'total_amount' => $itemTotal,
                'metadata' => $itemMetadata,
            ]);
        }

        Log::info('Created OrderItem records from order JSON', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'items_count' => count($items),
        ]);
    }

    /**
     * Dispatch appropriate renewal jobs based on order items
     */
    private function dispatchRenewalJobs(Order $order): void
    {
        $items = $order->items ?? [];
        $hasDomainRenewals = false;
        $hasSubscriptionRenewals = false;

        foreach ($items as $item) {
            $itemType = $item['attributes']['type'] ?? null;

            if ($itemType === 'renewal') {
                $hasDomainRenewals = true;
            } elseif ($itemType === 'subscription_renewal') {
                $hasSubscriptionRenewals = true;
            }
        }

        if ($hasDomainRenewals) {
            Log::info('Dispatching ProcessDomainRenewalJob', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            dispatch(new ProcessDomainRenewalJob($order));
        }

        if ($hasSubscriptionRenewals) {
            Log::info('Dispatching ProcessSubscriptionRenewalJob', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            dispatch(new ProcessSubscriptionRenewalJob($order));
        }

        if (! $hasDomainRenewals && ! $hasSubscriptionRenewals) {
            Log::warning('No renewal items found in order, no jobs dispatched', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }

    /**
     * Get display name for cart item (plan name only for subscription renewals and hosting)
     */
    private function getItemDisplayName($item): string
    {
        // Handle both array and object attribute access
        $itemType = $item->attributes->get('type') ?? $item->attributes['type'] ?? 'registration';
        $itemName = $item->name ?? '';

        // For subscription renewals and hosting, show only plan name
        if (in_array($itemType, ['subscription_renewal', 'hosting'], true)) {
            $hostingPlanId = $item->attributes->get('hosting_plan_id');

            if ($hostingPlanId) {
                $plan = HostingPlan::query()->find($hostingPlanId);

                if ($plan && $plan->name) {
                    return $plan->name;
                }
            }

            // Fallback: try to extract plan name from metadata
            $metadata = $item->attributes->get('metadata') ?? $item->attributes['metadata'] ?? [];
            $planData = $metadata['plan'] ?? null;

            if ($planData && isset($planData['name']) && $planData['name'] !== 'N/A') {
                return $planData['name'];
            }

            // Last resort: parse the name to extract plan name
            // Format: "domain - Plan Name (Renewal)" or "domain Hosting (cycle)" or "Hosting - Plan Name (Renewal)"
            if ($itemName && str_contains($itemName, ' - ')) {
                // Format: "domain - Plan Name (Renewal)" or "Hosting - Plan Name (Renewal)"
                $parts = explode(' - ', $itemName, 2);
                if (count($parts) === 2) {
                    $planPart = $parts[1];
                    // Remove "(Renewal)" suffix
                    $planPart = preg_replace('/\s*\(Renewal\)\s*$/i', '', $planPart);
                    $planPart = mb_trim($planPart);

                    // Don't return if it's "N/A" or empty
                    if ($planPart && $planPart !== 'N/A') {
                        return $planPart;
                    }
                }
            }

            if ($itemName && str_contains($itemName, ' Hosting (')) {
                // Format: "domain Hosting (cycle)"
                $planName = str_replace(' Hosting (', '', $itemName);
                $planName = preg_replace('/\s*\([^)]*\)\s*$/', '', $planName);
                $planName = mb_trim($planName);

                // Don't return if it's "N/A" or empty
                if ($planName && $planName !== 'N/A') {
                    return $planName;
                }
            }

            // If all else fails and we have a name, try to clean it up
            if ($itemName && $itemName !== 'N/A') {
                // Remove common prefixes like "N/A - " or "Hosting - "
                $cleaned = preg_replace('/^(N\/A|N\/A\s*-\s*|Hosting\s*-\s*)/i', '', $itemName);
                $cleaned = mb_trim($cleaned);

                if ($cleaned && $cleaned !== 'N/A') {
                    return $cleaned;
                }
            }
        }

        // For other item types (domains), return the name as-is, but filter out "N/A"
        if ($itemName && $itemName !== 'N/A') {
            return $itemName;
        }

        // Ultimate fallback
        return 'Item';
    }

    /**
     * Get formatted period for display in Stripe checkout
     */
    private function getItemPeriod($item): string
    {
        // Handle both array and object attribute access
        $itemType = $item->attributes->get('type') ?? $item->attributes['type'] ?? 'registration';

        // For subscription renewals, use duration_months or quantity to determine the actual period
        if ($itemType === 'subscription_renewal') {
            // Check duration_months first, then quantity
            $durationMonths = $item->attributes->get('duration_months') ?? $item->attributes['duration_months'] ?? null;

            if (! $durationMonths && $item->quantity) {
                // If duration_months is not set, use quantity (which should be months for subscription renewals)
                $durationMonths = $item->quantity;
            }

            if ($durationMonths) {
                return $this->formatDurationLabel((int) $durationMonths).' renewal';
            }

            // Fallback: try billing_cycle if duration_months is not available
            $billingCycle = $item->attributes->get('billing_cycle') ?? $item->attributes['billing_cycle'] ?? null;
            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);
                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum).' renewal';
                }
            }
        }

        // For hosting items, use billing_cycle to determine the period
        if ($itemType === 'hosting') {
            $billingCycle = $item->attributes->get('billing_cycle') ?? $item->attributes['billing_cycle'] ?? null;

            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);

                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum);
                }
            }
        }

        // For domain renewals and registrations, use quantity as years
        $years = $item->quantity ?? 1;
        $suffix = ($itemType === 'renewal') ? 'renewal' : 'of registration';

        return $years.' '.Str::plural('year', $years).' '.$suffix;
    }

    /**
     * Format billing cycle enum to readable label
     */
    private function formatBillingCycleLabel(BillingCycle $cycle): string
    {
        return match ($cycle) {
            BillingCycle::Monthly => '1 month',
            BillingCycle::Quarterly => '3 months',
            BillingCycle::SemiAnnually => '6 months',
            BillingCycle::Annually => '1 year',
            BillingCycle::Biennially => '2 years',
            BillingCycle::Triennially => '3 years',
        };
    }

    /**
     * Format duration in months to readable label
     */
    private function formatDurationLabel(int $months): string
    {
        if ($months < 12) {
            return $months.' '.Str::plural('month', $months);
        }

        $years = (int) ($months / 12);

        return $years.' '.Str::plural('year', $years);
    }
}
