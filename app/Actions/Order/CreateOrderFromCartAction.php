<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CartPriceConverter;
use Darryldecode\Cart\CartCollection;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CreateOrderFromCartAction
{
    public function __construct(
        private CartPriceConverter $cartPriceConverter
    ) {}

    /**
     * Create an order from cart items
     *
     * @param  array<string, int|null>  $contactIds
     * @param  array<string, mixed>|null  $billingData
     * @param  array<string, mixed>|null  $coupon
     *
     * @throws Throwable
     */
    public function handle(
        User $user,
        CartCollection $cartItems,
        string $currency,
        string $paymentMethod,
        array $contactIds = [],
        ?array $billingData = null,
        ?array $coupon = null,
        float $discountAmount = 0.0
    ): Order {
        return DB::transaction(function () use ($user, $cartItems, $currency, $paymentMethod, $contactIds, $billingData, $coupon, $discountAmount): Order {
            $currencyModel = Currency::query()->where('code', $currency)->firstOrFail();
            $orderType = $this->determineOrderType($cartItems);
            $subtotal = $this->cartPriceConverter->calculateCartSubtotal($cartItems, $currency);
            $total = max(0, $subtotal - $discountAmount);

            $billingContactId = $contactIds['billing'] ?? null;
            $contact = $billingContactId ? Contact::query()->find($billingContactId) : null;

            if (! $contact && in_array($orderType, ['renewal', 'hosting'], true)) {
                $billingEmail = $user->email;
                $billingName = $user->name;
                $billingAddress = [];
            } else {
                $billingEmail = $billingData['billing_email'] ?? $contact?->email ?? $user->email;
                $billingName = $billingData['billing_name'] ?? $contact?->full_name ?? $user->name;
                $billingAddress = $billingData['billing_address'] ?? ($contact ? [
                    'address_one' => $contact->address_one ?? '',
                    'address_two' => $contact->address_two ?? '',
                    'city' => $contact->city ?? '',
                    'state_province' => $contact->state_province ?? '',
                    'postal_code' => $contact->postal_code ?? '',
                    'country_code' => $contact->country_code ?? '',
                ] : []);
            }

            $couponCode = $coupon['code'] ?? null;
            $discountType = $coupon['type']['value'] ?? $coupon['type'] ?? null;

            $metadata = [];
            if (isset($contactIds['registrant'])) {
                $metadata['selected_contact_id'] = $contactIds['registrant'];
            }

            foreach (['registrant', 'admin', 'tech', 'billing'] as $type) {
                if (isset($contactIds[$type])) {
                    $metadata['contact_ids'][$type] = $contactIds[$type];
                }
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'type' => $orderType,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'total_amount' => $total,
                'subtotal' => $subtotal,
                'tax' => 0,
                'currency' => $currency,
                'coupon_code' => $couponCode,
                'discount_type' => $discountType,
                'discount_amount' => $discountAmount,
                'billing_email' => $billingEmail,
                'billing_name' => $billingName,
                'billing_address' => $billingAddress,
                'items' => $cartItems->map(fn ($item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'attributes' => $item->attributes->toArray(),
                ])->toArray(),
                'metadata' => $metadata,
            ]);

            foreach ($cartItems as $item) {
                // Use original_currency if preserved during conversion, otherwise use current currency
                $itemCurrency = $item->attributes->original_currency ?? $item->attributes->currency ?? 'USD';
                $originalPrice = $item->attributes->original_price ?? $item->price;
                $itemType = $item->attributes->type ?? 'registration';
                $domainId = $item->attributes->domain_id ?? null;
                $metadata = $item->attributes->get('metadata');

                $itemCurrencyModel = Currency::query()->where('code', $itemCurrency)->first();
                $originalExchangeRate = $itemCurrencyModel ? $itemCurrencyModel->exchange_rate : 1.0;
                $orderExchangeRate = $currencyModel->exchange_rate;

                $exchangeRate = $originalExchangeRate > 0 && $orderExchangeRate > 0
                    ? $orderExchangeRate / $originalExchangeRate
                    : 1.0;

                try {
                    $convertedItemPrice = $this->cartPriceConverter->convertItemPrice($item, $currency);
                    $convertedItemTotal = $this->cartPriceConverter->calculateItemTotal($item, $currency);
                } catch (Exception $exception) {
                    Log::error('Failed to convert item price for OrderItem', [
                        'item_id' => $item->id,
                        'item_type' => $itemType,
                        'item_currency' => $itemCurrency,
                        'order_currency' => $currency,
                        'error' => $exception->getMessage(),
                    ]);
                    throw $exception;
                }

                $itemMetadata = $metadata ? (is_array($metadata) ? $metadata : []) : [];

                $subscriptionId = $item->attributes->subscription_id ?? null;
                if ($subscriptionId) {
                    $itemMetadata['subscription_id'] = $subscriptionId;
                }

                if ($itemType === 'subscription_renewal') {
                    $billingCycle = $item->attributes->billing_cycle ?? null;
                    if ($billingCycle) {
                        $itemMetadata['billing_cycle'] = $billingCycle;
                    }

                    if (isset($item->attributes->duration_months)) {
                        $itemMetadata['duration_months'] = (int) $item->attributes->duration_months;
                    } else {
                        $itemMetadata['duration_months'] = (int) $item->quantity;
                    }
                }

                if ($itemType === 'hosting') {
                    if (isset($item->attributes->duration_months)) {
                        $itemMetadata['duration_months'] = (int) $item->attributes->duration_months;
                    } else {
                        $itemMetadata['duration_months'] = (int) $item->quantity;
                    }

                    if (isset($item->attributes->billing_cycle)) {
                        $itemMetadata['billing_cycle'] = $item->attributes->billing_cycle;
                    }

                    if (isset($item->attributes->hosting_plan_id)) {
                        $itemMetadata['hosting_plan_id'] = $item->attributes->hosting_plan_id;
                    }

                    if (isset($item->attributes->hosting_plan_price_id)) {
                        $itemMetadata['hosting_plan_price_id'] = $item->attributes->hosting_plan_price_id;
                    }

                    if (isset($item->attributes->linked_domain)) {
                        $itemMetadata['linked_domain'] = $item->attributes->linked_domain;
                    }
                }

                $years = $item->attributes->years ?? $item->quantity;
                if ($itemType === 'renewal') {
                    $years = $item->quantity;
                }

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'domain_name' => $item->attributes->domain_name ?? $item->name,
                    'domain_type' => $itemType,
                    'domain_id' => $domainId,
                    'price' => $convertedItemPrice,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'quantity' => $item->quantity,
                    'years' => $years,
                    'total_amount' => $convertedItemTotal,
                    'metadata' => array_merge($itemMetadata, [
                        'original_currency' => $itemCurrency,
                        'original_price' => $originalPrice,
                    ]),
                ]);
            }

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'total_amount' => $total,
                'currency' => $currency,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_code' => $couponCode,
            ]);

            return $order->fresh(['orderItems']);
        });
    }

    private function determineOrderType(CartCollection $cartItems): string
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
