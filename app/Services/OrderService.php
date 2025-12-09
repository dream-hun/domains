<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class OrderService
{
    public function __construct(
        private DomainRegistrationService $domainRegistrationService,
        private NotificationService $notificationService,
        private RenewalService $renewalService,
        private SubscriptionRenewalService $subscriptionRenewalService,
        private HostingSubscriptionService $hostingSubscriptionService,
    ) {}

    public function createOrder(array $data): Order
    {
        $currency = Currency::query()->where('code', $data['currency'])->first();

        // Determine order type based on cart items
        $orderType = $this->determineOrderType($data['cart_items']);

        // Get billing contact
        $billingContactId = $data['contact_ids']['billing'] ?? null;
        $contact = $billingContactId ? Contact::query()->find($billingContactId) : null;

        // For renewals or hosting-only orders, use user's email if no contact specified
        if (! $contact && in_array($orderType, ['renewal', 'hosting'], true)) {
            $user = User::query()->find($data['user_id']);
            $billingEmail = $user->email;
            $billingName = $user->name;
            $billingAddress = [];
        } else {
            $billingEmail = $contact->email ?? '';
            $billingName = $contact->full_name ?? '';
            $billingAddress = [
                'address_one' => $contact->address_one ?? '',
                'address_two' => $contact->address_two ?? '',
                'city' => $contact->city ?? '',
                'state_province' => $contact->state_province ?? '',
                'postal_code' => $contact->postal_code ?? '',
                'country_code' => $contact->country_code ?? '',
            ];
        }

        // Calculate total - items already have correct currency from cart
        $total = 0;
        foreach ($data['cart_items'] as $item) {
            $total += $item->getPriceSum();
        }

        $discountAmount = $data['discount_amount'] ?? 0;
        $convertedTotal = max(0, $total - $discountAmount);

        $couponCode = data_get($data, 'coupon.code');
        $discountType = data_get($data, 'coupon.type.value');

        $order = Order::query()->create([
            'user_id' => $data['user_id'],
            'order_number' => Order::generateOrderNumber(),
            'type' => $orderType,
            'status' => 'pending',
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'total_amount' => $convertedTotal,
            'subtotal' => $total,
            'tax' => 0,
            'currency' => $currency->code,
            'coupon_code' => $couponCode,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'billing_email' => $billingEmail,
            'billing_name' => $billingName,
            'billing_address' => $billingAddress,
            'items' => $data['cart_items']->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'attributes' => $item->attributes->toArray(),
            ])->toArray(),
        ]);

        // Create order items - prices already in correct currency
        foreach ($data['cart_items'] as $item) {
            // No conversion needed - items are already in the correct currency
            $itemPrice = $item->price;
            $itemTotal = $item->getPriceSum();
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemType = $item->attributes->type ?? 'registration';
            $domainId = $item->attributes->domain_id ?? null;
            $metadata = $item->attributes->get('metadata');

            // Get the exchange rate for the item's currency
            $itemCurrencyModel = Currency::query()->where('code', $itemCurrency)->first();
            $exchangeRate = $itemCurrencyModel ? $itemCurrencyModel->exchange_rate : 1.0;

            // For subscription renewals, metadata might have subscription_id
            $subscriptionId = $item->attributes->subscription_id ?? null;
            $itemMetadata = $metadata ?? [];

            if ($subscriptionId) {
                $itemMetadata['subscription_id'] = $subscriptionId;
            }

            // For subscription renewals, ensure billing_cycle is included in metadata
            if ($itemType === 'subscription_renewal') {
                $billingCycle = $item->attributes->billing_cycle ?? null;
                if ($billingCycle) {
                    $itemMetadata['billing_cycle'] = $billingCycle;
                }
            }

            OrderItem::query()->create([
                'order_id' => $order->id,
                'domain_name' => $item->attributes->domain_name ?? $item->name,
                'domain_type' => $itemType,
                'domain_id' => $domainId,
                'price' => $itemPrice,
                'currency' => $itemCurrency,
                'exchange_rate' => $exchangeRate,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'total_amount' => $itemTotal,
                'metadata' => $itemMetadata,
            ]);
        }

        return $order->fresh(['orderItems']);
    }

    public function processDomainRegistrations(Order $order, array $contactIds = []): void
    {
        $order->update(['status' => 'processing']);

        try {
            // Check order type
            if ($order->type === 'subscription_renewal') {
                // Process subscription renewals via job
                Log::info('Processing subscription renewal order', ['order_id' => $order->id]);
                \App\Jobs\ProcessSubscriptionRenewalJob::dispatch($order);
            } elseif ($order->type === 'renewal') {
                // Process renewals
                Log::info('Processing renewal order', ['order_id' => $order->id]);
                $results = $this->renewalService->processDomainRenewals($order);

                // Update order status based on results (for renewals)
                if (empty($results['failed'])) {
                    $order->update(['status' => 'completed']);
                } elseif (empty($results['successful'])) {
                    $order->update(['status' => 'failed']);
                    $this->notificationService->notifyAdminOfFailedRegistration($order, $results);
                } else {
                    $order->update(['status' => 'partially_completed']);
                    $this->notificationService->notifyAdminOfPartialFailure($order, $results);
                }
            } elseif ($order->type === 'hosting') {
                // Hosting-only orders don't require domain registration
                Log::info('Processing hosting-only order', ['order_id' => $order->id]);
                $order->update(['status' => 'completed']);
            } else {
                // Process registrations/transfers - requires contact information
                throw_unless(isset($contactIds['registrant'], $contactIds['admin'], $contactIds['tech'], $contactIds['billing']), Exception::class, 'All contact IDs (registrant, admin, tech, billing) are required for domain registration.');
                $contacts = [
                    'registrant' => $contactIds['registrant'],
                    'admin' => $contactIds['admin'],
                    'technical' => $contactIds['tech'],
                    'billing' => $contactIds['billing'],
                ];
                Log::info('Processing registration order', ['order_id' => $order->id]);
                // Use DomainRegistrationService which handles status updates and notifications
                $this->domainRegistrationService->processDomainRegistrations($order, $contacts);
            }

            // Provision hosting subscriptions for any hosting order items
            $this->hostingSubscriptionService->createSubscriptionsFromOrder($order);

        } catch (Exception $exception) {
            Log::error('Order processing failed', [
                'order_id' => $order->id,
                'order_type' => $order->type,
                'error' => $exception->getMessage(),
            ]);

            $order->update([
                'status' => 'requires_attention',
                'notes' => 'Payment succeeded but processing failed: '.$exception->getMessage(),
            ]);

            $this->notificationService->notifyAdminOfCriticalFailure($order, $exception);
        }
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $order->user->notify(new OrderConfirmationNotification($order));
    }

    /**
     * Determine order type based on cart items
     */
    private function determineOrderType($cartItems): string
    {
        $hasRegistration = false;
        $hasRenewal = false;
        $hasTransfer = false;
        $hasHosting = false;
        $hasSubscriptionRenewal = false;

        foreach ($cartItems as $item) {
            $itemType = $item->attributes->type ?? 'registration';

            switch ($itemType) {
                case 'renewal':
                    $hasRenewal = true;
                    break;
                case 'transfer':
                    $hasTransfer = true;
                    break;
                case 'hosting':
                    $hasHosting = true;
                    break;
                case 'subscription_renewal':
                    $hasSubscriptionRenewal = true;
                    break;
                default:
                    $hasRegistration = true;
            }
        }

        // If all items are subscription renewals, mark as subscription_renewal order
        if ($hasSubscriptionRenewal && ! $hasRenewal && ! $hasRegistration && ! $hasTransfer && ! $hasHosting) {
            return 'subscription_renewal';
        }

        // If all items are renewals, mark as renewal order
        if ($hasRenewal && ! $hasRegistration && ! $hasTransfer) {
            return 'renewal';
        }

        // If all items are transfers, mark as transfer order
        if ($hasTransfer && ! $hasRegistration && ! $hasRenewal) {
            return 'transfer';
        }

        // If cart has hosting and no domain operations, mark as hosting order
        // Also check if hosting items don't require domain
        if ($hasHosting && ! $hasRegistration && ! $hasRenewal && ! $hasTransfer) {
            return 'hosting';
        }

        // If cart has only hosting without domains (domain_required = false)
        $allHostingWithoutDomain = true;
        foreach ($cartItems as $item) {
            $itemType = $item->attributes->type ?? 'registration';
            if ($itemType !== 'hosting') {
                $allHostingWithoutDomain = false;
                break;
            }

            // Check if this hosting requires a domain
            if ($item->attributes->domain_required ?? false) {
                $allHostingWithoutDomain = false;
                break;
            }
        }

        if ($allHostingWithoutDomain && $hasHosting) {
            return 'hosting';
        }

        // Default to registration (or mixed)
        return 'registration';
    }
}
