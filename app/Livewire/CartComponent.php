<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use App\Models\Coupon;
use App\Models\Domain;
use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use App\Services\RenewalService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CartComponent extends Component
{
    public $items;

    public $subtotalAmount = 0;

    public $totalAmount = 0;

    public $currency;

    public ?string $couponCode = null;

    public ?Coupon $appliedCoupon = null;

    public float $discountAmount = 0;

    public bool $isCouponApplied = false;

    protected $listeners = ['refreshCart' => '$refresh', 'currencyChanged' => 'updateCurrency'];

    public function mount(): void
    {
        $this->currency = CurrencyHelper::getUserCurrency();

        // Restore coupon from session if exists
        if (session()->has('coupon')) {
            $couponData = session('coupon');
            $this->appliedCoupon = Coupon::query()->where('code', $couponData['code'])->first();
            if ($this->appliedCoupon instanceof Coupon) {
                $this->isCouponApplied = true;
                $this->couponCode = $this->appliedCoupon->code;
            }
        }

        $this->updateCartTotal();
    }

    public function updateCurrency(string $currency): void
    {
        Log::info('CartComponent received currency change', [
            'new_currency' => $currency,
            'old_currency' => $this->currency,
        ]);

        $this->currency = $currency;
        $this->updateCartTotal();

        // Recalculate discount if coupon is applied
        if ($this->isCouponApplied && $this->appliedCoupon) {
            $this->calculateDiscount();

            // Update coupon session with new currency
            session([
                'coupon' => [
                    'code' => $this->appliedCoupon->code,
                    'type' => $this->appliedCoupon->type->value,
                    'value' => $this->appliedCoupon->value,
                    'discount_amount' => $this->discountAmount,
                    'currency' => $this->currency,
                ],
            ]);
        }

        // Update session currency
        session(['selected_currency' => $this->currency]);
    }

    public function updateCartTotal(): void
    {
        // Get cart content and maintain original order
        $cartContent = Cart::getContent();

        // Sort by creation timestamp to maintain consistent order
        // This ensures items stay in the same order regardless of updates
        $this->items = $cartContent->sortBy(fn ($item) => $item->attributes->get('added_at', 0));

        // Calculate totals with currency conversion
        $subtotal = 0;

        try {
            foreach ($this->items as $item) {
                $itemCurrency = $item->attributes->currency ?? 'USD';
                $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);
                $itemTotal = $convertedPrice * $item->quantity;
                $subtotal += $itemTotal;
            }

            $this->subtotalAmount = $subtotal;

            // Apply discount to total
            $total = $subtotal - $this->discountAmount;

            // Ensure total never goes below zero
            $this->totalAmount = max(0, $total);
        } catch (Exception $exception) {
            Log::error('Failed to update cart totals due to currency conversion error', [
                'currency' => $this->currency,
                'error' => $exception->getMessage(),
            ]);

            $this->subtotalAmount = 0;
            $this->totalAmount = 0;

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to convert cart totals to the selected currency. Please try again or switch currencies.',
            ]);
        }
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function formattedSubtotal(): string
    {
        return CurrencyHelper::formatMoney($this->subtotalAmount, $this->currency);
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function formattedTotal(): string
    {
        return CurrencyHelper::formatMoney($this->totalAmount, $this->currency);
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function formattedDiscount(): string
    {
        try {
            return CurrencyHelper::formatMoney($this->discountAmount, $this->currency);
        } catch (Exception) {
            return CurrencyHelper::formatMoney(0, $this->currency);
        }
    }

    /**
     * Get formatted price for individual cart item
     *
     * @throws Exception
     */
    public function getFormattedItemPrice($item): string
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';

        try {
            $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);

            return CurrencyHelper::formatMoney($convertedPrice, $this->currency);
        } catch (Exception $exception) {
            Log::warning('Falling back to original currency for cart item price display', [
                'display_currency' => $this->currency,
                'item_currency' => $itemCurrency,
                'error' => $exception->getMessage(),
            ]);

            try {
                return CurrencyHelper::formatMoney($item->price, $itemCurrency);
            } catch (Exception) {
                return $itemCurrency.' '.number_format($item->price, 2);
            }
        }
    }

    /**
     * Get formatted total price for individual cart item (price * quantity)
     */
    public function getFormattedItemTotal($item): string
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';

        try {
            $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);
            $total = $convertedPrice * $item->quantity;

            return CurrencyHelper::formatMoney($total, $this->currency);
        } catch (Exception $exception) {
            Log::warning('Falling back to original currency for cart item total display', [
                'display_currency' => $this->currency,
                'item_currency' => $itemCurrency,
                'error' => $exception->getMessage(),
            ]);

            $fallbackTotal = $item->price * $item->quantity;

            try {
                return CurrencyHelper::formatMoney($fallbackTotal, $itemCurrency);
            } catch (Exception) {
                return $itemCurrency.' '.number_format($fallbackTotal, 2);
            }
        }
    }

    public function updateQuantity($id, $quantity): void
    {
        try {
            $currentItem = Cart::get($id);

            if (! $currentItem) {
                return;
            }

            if ($currentItem->attributes->get('type', 'registration') === 'hosting') {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Hosting plans are billed as subscriptions and cannot change quantity.',
                ]);

                return;
            }

            if ($quantity > 0 && $quantity <= 10) {
                // Update quantity while preserving attributes
                Cart::update($id, [
                    'quantity' => [
                        'relative' => false,
                        'value' => (int) $quantity,
                    ],
                ]);

                // Make sure we preserve the original added_at timestamp
                // This ensures the item maintains its position in the list
                if (! $currentItem->attributes->has('added_at')) {
                    Cart::update($id, [
                        'attributes' => [
                            'added_at' => now()->timestamp,
                        ],
                    ]);
                }

                $this->updateCartTotal();

                // Recalculate discount if coupon is applied
                if ($this->isCouponApplied && $this->appliedCoupon) {
                    $this->calculateDiscount();

                    // Update coupon session with new discount amount
                    session([
                        'coupon' => [
                            'code' => $this->appliedCoupon->code,
                            'type' => $this->appliedCoupon->type->value,
                            'value' => $this->appliedCoupon->value,
                            'discount_amount' => $this->discountAmount,
                            'currency' => $this->currency,
                        ],
                    ]);
                }

                $this->dispatch('refreshCart');

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Quantity updated successfully',
                ]);
            }
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to update quantity',
            ]);
        }
    }

    public function addToCart($domain, $price, $currency = null): void
    {
        try {
            // Convert price string to numeric value (remove currency symbols)
            $numericPrice = (float) preg_replace('/[^\d.]/', '', (string) $price);
            $itemCurrency = $currency ?? $this->currency;

            Cart::add([
                'id' => $domain,
                'name' => $domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'domain_name' => $domain,
                    'currency' => $itemCurrency,
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->updateCartTotal();

            // Recalculate discount if coupon is applied
            if ($this->isCouponApplied && $this->appliedCoupon) {
                $this->calculateDiscount();

                // Update coupon session with new discount amount
                session([
                    'coupon' => [
                        'code' => $this->appliedCoupon->code,
                        'type' => $this->appliedCoupon->type->value,
                        'value' => $this->appliedCoupon->value,
                        'discount_amount' => $this->discountAmount,
                        'currency' => $this->currency,
                    ],
                ]);
            }

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Domain added to cart successfully',
            ]);
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add domain to cart',
            ]);
        }
    }

    /**
     * Add domain renewal to cart
     */
    public function addRenewalToCart(int $domainId, int $years = 1): void
    {
        try {
            $domain = Domain::with('domainPrice')->findOrFail($domainId);
            $user = auth()->user();

            if (! $user) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You must be logged in to renew a domain',
                ]);

                return;
            }

            // Validate ownership
            if ($domain->owner_id !== $user->id && ! $user->isAdmin()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You do not own this domain',
                ]);

                return;
            }

            $renewalService = app(RenewalService::class);

            $domainPrice = $renewalService->resolveDomainPrice($domain);

            if (! $domainPrice) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Pricing information not available for this domain',
                ]);

                return;
            }

            $minimumValidation = $renewalService->validateStripeMinimumAmountForRenewal($domain, $domainPrice, $years);

            if (! $minimumValidation['valid']) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $minimumValidation['message'],
                ]);

                return;
            }

            $domain->setRelation('domainPrice', $domainPrice);

            $canRenew = $renewalService->canRenewDomain($domain, $user);

            if (! $canRenew['can_renew']) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $canRenew['reason'] ?? 'Cannot renew this domain',
                ]);

                return;
            }

            // Get renewal price
            $priceData = $renewalService->getRenewalPrice($domain, $years);
            $price = $priceData['unit_price'];
            $totalPrice = $priceData['total_price'];
            $currency = $priceData['currency'];

            // Create unique cart ID for renewal
            $cartId = 'renewal-'.$domainId;

            // Check if already in cart
            if (Cart::get($cartId)) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'This domain renewal is already in your cart',
                ]);

                return;
            }

            // Add to cart
            Cart::add([
                'id' => $cartId,
                'name' => $domain->name.' (Renewal)',
                'price' => $price,
                'quantity' => $years,
                'attributes' => [
                    'type' => 'renewal',
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'current_expiry' => $domain->expires_at?->format('Y-m-d'),
                    'tld' => $domainPrice->tld,
                    'currency' => $currency,
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->updateCartTotal();

            // Recalculate discount if coupon is applied
            if ($this->isCouponApplied && $this->appliedCoupon) {
                $this->calculateDiscount();

                // Update coupon session with new discount amount
                session([
                    'coupon' => [
                        'code' => $this->appliedCoupon->code,
                        'type' => $this->appliedCoupon->type->value,
                        'value' => $this->appliedCoupon->value,
                        'discount_amount' => $this->discountAmount,
                        'currency' => $this->currency,
                    ],
                ]);
            }

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => sprintf('Domain renewal for %s added to cart for %d year(s)', $domain->name, $years),
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to add renewal to cart', [
                'domain_id' => $domainId,
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add renewal to cart: '.$exception->getMessage(),
            ]);
        }
    }

    public function removeItem($id): void
    {
        try {
            $currentItem = Cart::get($id);

            Cart::remove($id);

            if ($currentItem && $currentItem->attributes->get('type', 'registration') === 'domain') {
                $domainName = $currentItem->attributes->get('domain_name')
                    ?? $currentItem->attributes->get('domain')
                    ?? $currentItem->name;

                if ($domainName) {
                    $this->removeHostingForDomain((string) $domainName);
                }
            }
            $this->updateCartTotal();

            // Recalculate discount if coupon is applied
            if ($this->isCouponApplied && $this->appliedCoupon) {
                $this->calculateDiscount();

                // Update coupon session with new discount amount
                session([
                    'coupon' => [
                        'code' => $this->appliedCoupon->code,
                        'type' => $this->appliedCoupon->type->value,
                        'value' => $this->appliedCoupon->value,
                        'discount_amount' => $this->discountAmount,
                        'currency' => $this->currency,
                    ],
                ]);
            }

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Item removed from cart successfully',
            ]);
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to remove item from cart',
            ]);
        }
    }

    public function applyCoupon(): void
    {
        try {
            // Validate coupon code is not empty
            if (in_array($this->couponCode, [null, '', '0'], true)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Please enter a coupon code',
                ]);

                return;
            }

            // Check if cart has items
            if ($this->items->isEmpty()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Add items to cart before applying coupon',
                ]);

                return;
            }

            // Validate coupon using CouponService
            $couponService = app(CouponService::class);
            $this->appliedCoupon = $couponService->validateCoupon($this->couponCode);
            $this->isCouponApplied = true;

            // Calculate discount (this will set discountAmount and update totalAmount)
            $this->calculateDiscount();

            // Store coupon data in session
            session([
                'coupon' => [
                    'code' => $this->appliedCoupon->code,
                    'type' => $this->appliedCoupon->type->value,
                    'value' => $this->appliedCoupon->value,
                    'discount_amount' => $this->discountAmount,
                    'currency' => $this->currency,
                ],
            ]);

            // Dispatch event to update other components (like CartTotal)
            $this->dispatch('couponApplied');
            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => sprintf("Coupon '%s' applied! You saved ", $this->appliedCoupon->code).CurrencyHelper::formatMoney($this->discountAmount, $this->currency),
            ]);
        } catch (Exception $exception) {
            // Clear any partial coupon state
            $this->appliedCoupon = null;
            $this->isCouponApplied = false;
            $this->discountAmount = 0;

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function removeCoupon(): void
    {
        // Reset coupon properties
        $this->appliedCoupon = null;
        $this->discountAmount = 0;
        $this->isCouponApplied = false;
        $this->couponCode = null;

        session()->forget('coupon');

        $this->updateCartTotal();

        // Dispatch event to update other components (like CartTotal)
        $this->dispatch('couponRemoved');
        $this->dispatch('refreshCart');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Coupon removed successfully',
        ]);
    }

    /**
     * Prepare cart data for payment processing
     */
    public function prepareCartForPayment(): array
    {
        $cartItems = [];

        foreach ($this->items as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);

            // Build metadata array, including hosting-specific fields if present
            $metadata = $item->attributes->get('metadata', []);

            // For hosting items, ensure plan metadata is included
            if ($item->attributes->get('type') === 'hosting') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_price_id'] = $item->attributes->get('hosting_plan_price_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['linked_domain'] = $item->attributes->get('linked_domain');
                $metadata['is_existing_domain'] = $item->attributes->get('is_existing_domain');
            }

            $cartItems[] = [
                'domain_name' => $item->attributes->get('domain_name') ?? $item->name,
                'domain_type' => $item->attributes->get('type', 'registration'),
                'price' => $itemPrice,
                'currency' => $this->currency,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'domain_id' => $item->attributes->get('domain_id'),
                'metadata' => $metadata,
                'hosting_plan_id' => $item->attributes->get('hosting_plan_id'),
                'hosting_plan_price_id' => $item->attributes->get('hosting_plan_price_id'),
                'linked_domain' => $item->attributes->get('linked_domain'),
            ];
        }

        $paymentData = [
            'items' => $cartItems,
            'subtotal' => $this->subtotalAmount,
            'total' => $this->totalAmount,
            'currency' => $this->currency,
        ];

        if ($this->isCouponApplied && $this->appliedCoupon) {
            $paymentData['coupon'] = [
                'code' => $this->appliedCoupon->code,
                'type' => $this->appliedCoupon->type->value,
                'value' => $this->appliedCoupon->value,
                'discount_amount' => $this->discountAmount,
            ];
        }

        return $paymentData;
    }

    public function proceedToPayment(): void
    {
        if ($this->items->isEmpty()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Your cart is empty',
            ]);

            return;
        }

        try {
            // Prepare cart data with currency conversion
            $paymentData = $this->prepareCartForPayment();

            // Store prepared cart data in session
            session([
                'cart' => $paymentData['items'],
                'cart_subtotal' => $paymentData['subtotal'],
                'cart_total' => $paymentData['total'],
                'selected_currency' => $paymentData['currency'],
            ]);

            // Store coupon data if present
            if (isset($paymentData['coupon'])) {
                session(['coupon' => $paymentData['coupon']]);
            }

            $this->redirect(route('payment.index'));
        } catch (Exception $exception) {
            Log::error('Failed to prepare cart for payment', [
                'currency' => $this->currency,
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to prepare the cart for payment. Please try again or switch currencies.',
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.cart-component');
    }

    /**
     * Convert an amount from its original currency into the current display currency.
     *
     * @throws Exception
     */
    private function convertToDisplayCurrency(float $amount, string $fromCurrency): float
    {
        $fromCurrency = mb_strtoupper(mb_trim($fromCurrency));
        $toCurrency = mb_strtoupper(mb_trim($this->currency));

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        try {
            return CurrencyHelper::convert($amount, $fromCurrency, $toCurrency);
        } catch (Exception $exception) {
            Log::warning('Primary currency conversion failed in cart component', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'amount' => $amount,
                'error' => $exception->getMessage(),
            ]);

            try {
                $currencyService = app(CurrencyService::class);

                return $currencyService->convert($amount, $fromCurrency, $toCurrency);
            } catch (Exception $fallbackException) {
                Log::error('Currency conversion failed after fallback in cart component', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'amount' => $amount,
                    'error' => $fallbackException->getMessage(),
                ]);

                throw new Exception('Unable to convert currency.', $exception->getCode(), $exception);
            }
        }
    }

    private function calculateDiscount(): void
    {
        if (! $this->appliedCoupon || ! $this->isCouponApplied) {
            $this->discountAmount = 0;

            return;
        }

        $subtotal = $this->subtotalAmount;

        $couponService = app(CouponService::class);
        $discountedTotal = $couponService->applyCoupon($this->appliedCoupon, $subtotal);

        $discount = $subtotal - $discountedTotal;

        $this->discountAmount = max(0, min($discount, $subtotal));

        $this->totalAmount = max(0, $subtotal - $this->discountAmount);
    }

    private function removeHostingForDomain(string $domain): void
    {
        $cartContent = Cart::getContent();
        $target = mb_strtolower($domain);

        foreach ($cartContent as $item) {
            $linkedDomain = $item->attributes->get('linked_domain');

            if ($item->attributes->get('type') === 'hosting' && $linkedDomain && mb_strtolower((string) $linkedDomain) === $target) {
                Cart::remove($item->id);
            }
        }
    }
}
