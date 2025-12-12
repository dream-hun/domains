<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class OrderService
{
    public function __construct(
        private DomainRegistrationService $domainRegistrationService,
        private NotificationService $notificationService,
        private RenewalService $renewalService,
        private HostingSubscriptionService $hostingSubscriptionService,
    ) {}

    public function createOrder(array $data): Order
    {
        $currency = Currency::query()->where('code', $data['currency'])->first();
        $orderType = $this->determineOrderType($data['cart_items']);
        $billingContactId = $data['contact_ids']['billing'] ?? null;
        $contact = $billingContactId ? Contact::query()->find($billingContactId) : null;

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
        foreach ($data['cart_items'] as $item) {

            $itemPrice = $item->price;
            $itemTotal = $item->getPriceSum();
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemType = $item->attributes->type ?? 'registration';
            $domainId = $item->attributes->domain_id ?? null;
            $metadata = $item->attributes->get('metadata');

            $itemCurrencyModel = Currency::query()->where('code', $itemCurrency)->first();
            $exchangeRate = $itemCurrencyModel ? $itemCurrencyModel->exchange_rate : 1.0;
            $subscriptionId = $item->attributes->subscription_id ?? null;
            $itemMetadata = $metadata ?? [];

            if ($subscriptionId) {
                $itemMetadata['subscription_id'] = $subscriptionId;
            }

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

            if ($order->type === 'subscription_renewal') {

                Log::info('Processing subscription renewal order', ['order_id' => $order->id]);
                dispatch(new ProcessSubscriptionRenewalJob($order));
            } elseif ($order->type === 'renewal') {

                Log::info('Processing renewal order', ['order_id' => $order->id]);
                $results = $this->renewalService->processDomainRenewals($order);

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

                Log::info('Processing hosting-only order', ['order_id' => $order->id]);
                $order->update(['status' => 'completed']);
            } else {

                throw_unless(isset($contactIds['registrant'], $contactIds['admin'], $contactIds['tech'], $contactIds['billing']), Exception::class, 'All contact IDs (registrant, admin, tech, billing) are required for domain registration.');
                $contacts = [
                    'registrant' => $contactIds['registrant'],
                    'admin' => $contactIds['admin'],
                    'technical' => $contactIds['tech'],
                    'billing' => $contactIds['billing'],
                ];
                Log::info('Processing registration order', ['order_id' => $order->id]);

                $this->domainRegistrationService->processDomainRegistrations($order, $contacts);
            }

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
        } catch (Throwable) {
        }
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $order->user->notify(new OrderConfirmationNotification($order));
    }

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

        if ($hasSubscriptionRenewal && ! $hasRenewal && ! $hasRegistration && ! $hasTransfer && ! $hasHosting) {
            return 'subscription_renewal';
        }

        if ($hasRenewal && ! $hasRegistration && ! $hasTransfer) {
            return 'renewal';
        }

        if ($hasTransfer && ! $hasRegistration && ! $hasRenewal) {
            return 'transfer';
        }

        if ($hasHosting && ! $hasRegistration && ! $hasRenewal && ! $hasTransfer) {
            return 'hosting';
        }

        $allHostingWithoutDomain = true;
        foreach ($cartItems as $item) {
            $itemType = $item->attributes->type ?? 'registration';
            if ($itemType !== 'hosting') {
                $allHostingWithoutDomain = false;
                break;
            }

            if ($item->attributes->domain_required ?? false) {
                $allHostingWithoutDomain = false;
                break;
            }
        }

        if ($allHostingWithoutDomain && $hasHosting) {
            return 'hosting';
        }

        return 'registration';
    }
}
