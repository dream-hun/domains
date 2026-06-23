<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Helpers\CurrencyHelper;
use App\Models\Coupon;
use App\Services\CartPriceConverter;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Extracts cart-to-payment-data transformation out of the Livewire CartComponent
 * so that BillingService (a service layer class) does not depend on a UI component.
 */
final readonly class CartPreparationService
{
    public function __construct(
        private CartPriceConverter $priceConverter,
    ) {}

    /**
     * Build the structured payment data array from raw cart contents and totals.
     * Used by CartComponent::prepareCartForPayment() and BillingService::getPreparedCartData().
     *
     * @param  iterable  $items  Cart items (Darryldecode collection or array)
     * @param  string  $currency  Target display currency
     * @param  float  $subtotal  Pre-calculated subtotal in display currency
     * @param  float  $total  Pre-calculated total (after discounts) in display currency
     * @param  float  $discount  Discount amount applied
     * @param  ?Coupon  $coupon  Applied coupon model (null if none)
     */
    public function buildPaymentData(
        iterable $items,
        string $currency,
        float $subtotal,
        float $total,
        float $discount = 0,
        ?Coupon $coupon = null,
    ): array {
        $cartItems = [];

        foreach ($items as $item) {
            $itemType = $item->attributes->get('type', 'registration');

            try {
                $itemPrice = $this->priceConverter->convertItemPrice($item, $currency);
            } catch (Exception $exception) {
                Log::error('Failed to convert item price during cart preparation', [
                    'item_id' => $item->id,
                    'item_type' => $itemType,
                    'currency' => $currency,
                    'error' => $exception->getMessage(),
                ]);
                throw $exception;
            } catch (Throwable) {
                $itemPrice = (float) $item->price;
            }

            $metadata = $item->attributes->get('metadata', []);

            if ($itemType === 'hosting') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_pricing_id'] = $item->attributes->get('hosting_plan_pricing_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['linked_domain'] = $item->attributes->get('linked_domain');
                $metadata['is_existing_domain'] = $item->attributes->get('is_existing_domain');
                $metadata['duration_months'] = (int) ($item->attributes->get('duration_months') ?? $item->quantity);
            }

            if ($itemType === 'subscription_renewal') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_pricing_id'] = $item->attributes->get('hosting_plan_pricing_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['subscription_id'] = $item->attributes->get('subscription_id');
                $metadata['duration_months'] = (int) ($item->attributes->get('duration_months') ?? $item->quantity);
            }

            $years = match ($itemType) {
                'subscription_renewal', 'hosting' => (int) ($item->quantity / 12),
                default => (int) $item->quantity,
            };

            $cartItems[] = [
                'domain_name' => $item->attributes->get('domain_name') ?? $item->name,
                'domain_type' => $itemType,
                'price' => $itemPrice,
                'currency' => $currency,
                'quantity' => $item->quantity,
                'years' => $years,
                'domain_id' => $item->attributes->get('domain_id'),
                'metadata' => $metadata,
                'hosting_plan_id' => $item->attributes->get('hosting_plan_id'),
                'hosting_plan_pricing_id' => $item->attributes->get('hosting_plan_pricing_id'),
                'linked_domain' => $item->attributes->get('linked_domain'),
            ];
        }

        $paymentData = [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'total' => $total,
            'currency' => $currency,
        ];

        if ($coupon instanceof Coupon && $discount > 0) {
            $paymentData['coupon'] = [
                'code' => $coupon->code,
                'type' => $coupon->type->value,
                'value' => $coupon->value,
                'discount_amount' => $discount,
            ];
        }

        return $paymentData;
    }

    /**
     * Build payment data directly from the Cart facade (no Livewire component needed).
     * Used as the last-resort fallback in BillingService when no pre-prepared data exists.
     *
     * @throws Exception
     */
    public function buildFromCartFacade(): array
    {
        $cartItems = Cart::getContent()->sortBy(
            fn (mixed $item): mixed => $item->attributes->get('added_at', 0)
        );

        throw_if($cartItems->isEmpty(), Exception::class, 'Cart is empty');

        $currency = CurrencyHelper::getUserCurrency();
        $subtotal = $this->priceConverter->calculateCartSubtotal($cartItems, $currency);
        $total = $subtotal;

        $coupon = null;
        $discount = 0.0;

        if (session()->has('coupon')) {
            $couponData = session('coupon');
            $coupon = Coupon::query()->where('code', $couponData['code'])->first();
            $discount = (float) ($couponData['discount_amount'] ?? 0);
            $total = max(0, $subtotal - $discount);
        }

        return $this->buildPaymentData($cartItems, $currency, $subtotal, $total, $discount, $coupon);
    }
}
