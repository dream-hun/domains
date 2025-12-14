<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use App\Services\CartPriceConverter;
use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use App\Services\OrderItemFormatterService;
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

        try {
            $cartPriceConverter = app(CartPriceConverter::class);

            foreach ($this->items as $item) {
                $itemType = $item->attributes->get('type', 'registration');

                if ($itemType === 'hosting') {
                    $durationMonths = $item->attributes->get('duration_months');

                    if (! $durationMonths) {
                        $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
                        $durationMonths = $this->getBillingCycleMonths($billingCycle);

                        $monthlyPrice = $item->attributes->get('monthly_unit_price');
                        if (! $monthlyPrice) {
                            $monthlyPrice = $durationMonths > 0 ? $item->price / $durationMonths : $item->price;
                        }

                        $existingAttributes = $item->attributes->all();
                        Cart::update($item->id, [
                            'quantity' => [
                                'relative' => false,
                                'value' => $durationMonths,
                            ],
                            'attributes' => array_merge($existingAttributes, [
                                'duration_months' => $durationMonths,
                                'monthly_unit_price' => $monthlyPrice,
                            ]),
                        ]);

                        $item = Cart::get($item->id);
                    } elseif ($item->quantity !== $durationMonths) {
                        $existingAttributes = $item->attributes->all();
                        Cart::update($item->id, [
                            'quantity' => [
                                'relative' => false,
                                'value' => (int) $durationMonths,
                            ],
                            'attributes' => $existingAttributes,
                        ]);

                        $item = Cart::get($item->id);
                    }
                }

                if ($itemType === 'subscription_renewal') {
                    $durationMonths = $item->attributes->get('duration_months');

                    if (! $durationMonths) {
                        $existingAttributes = $item->attributes->all();
                        Cart::update($item->id, [
                            'attributes' => array_merge($existingAttributes, [
                                'duration_months' => (int) $item->quantity,
                            ]),
                        ]);

                        $item = Cart::get($item->id);
                    } elseif ($item->quantity !== $durationMonths) {
                        $existingAttributes = $item->attributes->all();
                        Cart::update($item->id, [
                            'quantity' => [
                                'relative' => false,
                                'value' => (int) $durationMonths,
                            ],
                            'attributes' => $existingAttributes,
                        ]);

                        $item = Cart::get($item->id);
                    }
                }
            }

            // Use unified converter to calculate subtotal in display currency
            $subtotal = $cartPriceConverter->calculateCartSubtotal($this->items, $this->currency);

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
        try {
            $cartPriceConverter = app(CartPriceConverter::class);
            $convertedPrice = $cartPriceConverter->convertItemPrice($item, $this->currency);

            return CurrencyHelper::formatMoney($convertedPrice, $this->currency);
        } catch (Exception $exception) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
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
        try {
            $cartPriceConverter = app(CartPriceConverter::class);
            $total = $cartPriceConverter->calculateItemTotal($item, $this->currency);

            return CurrencyHelper::formatMoney($total, $this->currency);
        } catch (Exception $exception) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
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

            $itemType = $currentItem->attributes->get('type', 'registration');

            // For domain/registration items, check domain price max_years if available
            $maxQuantity = match ($itemType) {
                'subscription_renewal', 'hosting' => 48, // Maximum 4 years (48 months)
                'domain', 'registration' => $this->getDomainMaxYears($currentItem),
                default => 10,
            };

            if ($quantity >= 1 && $quantity <= $maxQuantity) {
                if (in_array($itemType, ['subscription_renewal', 'hosting'], true)) {
                    $existingAttributes = $currentItem->attributes->all();

                    // For subscription_renewal, get unit_price and update total_price
                    if ($itemType === 'subscription_renewal') {
                        $billingCycle = $existingAttributes['billing_cycle'] ?? 'monthly';
                        $displayUnitPrice = $existingAttributes['display_unit_price'] ?? $existingAttributes['unit_price'] ?? $currentItem->price;

                        // If billing cycle is annually, convert months to years for calculation
                        if ($billingCycle === 'annually') {
                            $years = (int) $quantity / 12;
                            $existingAttributes['total_price'] = $displayUnitPrice * $years;
                        } else {
                            // For monthly, use monthly price × quantity (in months)
                            $monthlyPrice = $existingAttributes['unit_price'] ?? $currentItem->price;
                            $existingAttributes['total_price'] = $monthlyPrice * (int) $quantity;
                        }
                    }

                    // For hosting, ensure monthly price is stored
                    if ($itemType === 'hosting') {
                        $monthlyPrice = $existingAttributes['monthly_unit_price'] ?? null;
                        if (! $monthlyPrice) {
                            $billingCycle = $existingAttributes['billing_cycle'] ?? 'monthly';
                            $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
                            $monthlyPrice = $billingCycleMonths > 0 ? $currentItem->price / $billingCycleMonths : $currentItem->price;
                        }
                        $existingAttributes['monthly_unit_price'] = $monthlyPrice;

                        $newBillingCycle = $this->getBillingCycleFromMonths((int) $quantity);
                        $existingAttributes['billing_cycle'] = $newBillingCycle;
                    }

                    Cart::update($id, [
                        'quantity' => [
                            'relative' => false,
                            'value' => (int) $quantity,
                        ],
                        'attributes' => array_merge($existingAttributes, [
                            'duration_months' => (int) $quantity,
                        ]),
                    ]);
                } else {
                    // For renewal items, also update the years attribute to match quantity
                    $existingAttributes = $currentItem->attributes->all();
                    if ($itemType === 'renewal') {
                        $existingAttributes['years'] = (int) $quantity;
                    }

                    Cart::update($id, [
                        'quantity' => [
                            'relative' => false,
                            'value' => (int) $quantity,
                        ],
                        'attributes' => $existingAttributes,
                    ]);
                }

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
            } else {
                $maxLabel = in_array($itemType, ['subscription_renewal', 'hosting'], true) ? '48 months (4 years)' : '10 years';
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => sprintf('Quantity must be between 1 and %s', $maxLabel),
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

            $minimumValidation = $renewalService->validateStripeMinimumAmountForRenewal($domainPrice, $years);

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
                    'years' => $years,
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

    /**
     * Add subscription renewal to cart
     */
    public function addSubscriptionRenewalToCart(int $subscriptionId, string $billingCycle): void
    {
        try {
            $subscription = Subscription::with(['plan', 'planPrice'])->findOrFail($subscriptionId);
            $user = auth()->user();

            if (! $user) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You must be logged in to renew a subscription',
                ]);

                return;
            }

            if ($subscription->user_id !== $user->id && ! $user->isAdmin()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You do not own this subscription',
                ]);

                return;
            }

            if (! $subscription->canBeRenewed()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'This subscription cannot be renewed at this time',
                ]);

                return;
            }

            $billingCycleEnum = BillingCycle::tryFrom($billingCycle);
            if (! $billingCycleEnum) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Invalid billing cycle selected',
                ]);

                return;
            }

            $monthlyPlanPrice = HostingPlanPrice::query()
                ->where('hosting_plan_id', $subscription->hosting_plan_id)
                ->where('billing_cycle', 'monthly')
                ->where('status', 'active')
                ->first();

            if (! $monthlyPlanPrice) {
                $monthlyPlanPrice = HostingPlanPrice::query()
                    ->where('hosting_plan_id', $subscription->hosting_plan_id)
                    ->where('status', 'active')
                    ->first();
            }

            if (! $monthlyPlanPrice) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Pricing information not available for this subscription',
                ]);

                return;
            }

            $userCurrency = $this->currency;
            $monthlyRenewalPrice = $monthlyPlanPrice->getPriceInCurrency('renewal_price', $userCurrency);
            $durationMonths = $this->getBillingCycleMonths($billingCycle);
            $cartId = 'subscription-renewal-'.$subscription->id;

            if (Cart::get($cartId)) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'This subscription renewal is already in your cart',
                ]);

                return;
            }

            Cart::add([
                'id' => $cartId,
                'name' => ($subscription->domain ?: 'Hosting').' - '.$subscription->plan->name.' (Renewal)',
                'price' => $monthlyRenewalPrice,
                'quantity' => $durationMonths,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'billing_cycle' => $billingCycle,
                    'hosting_plan_id' => $subscription->hosting_plan_id,
                    'hosting_plan_price_id' => $monthlyPlanPrice->id,
                    'domain' => $subscription->domain,
                    'current_expiry' => $subscription->expires_at->format('Y-m-d'),
                    'currency' => $userCurrency,
                    'monthly_unit_price' => $monthlyRenewalPrice,
                    'duration_months' => $durationMonths,
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

            $formatter = app(OrderItemFormatterService::class);
            $durationLabel = $formatter->formatDurationLabel($durationMonths);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => sprintf('Subscription renewal for %s added to cart (%s)', $subscription->plan->name, $durationLabel),
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to add subscription renewal to cart', [
                'subscription_id' => $subscriptionId,
                'billing_cycle' => $billingCycle,
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add subscription renewal to cart: '.$exception->getMessage(),
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
        $cartPriceConverter = app(CartPriceConverter::class);

        foreach ($this->items as $item) {
            $itemType = $item->attributes->get('type', 'registration');

            // Convert item price to display currency using unified converter
            try {
                $itemPrice = $cartPriceConverter->convertItemPrice($item, $this->currency);
            } catch (Exception $exception) {
                Log::error('Failed to convert item price in prepareCartForPayment', [
                    'item_id' => $item->id,
                    'item_type' => $itemType,
                    'currency' => $this->currency,
                    'error' => $exception->getMessage(),
                ]);
                throw $exception;
            }

            $metadata = $item->attributes->get('metadata', []);

            if ($itemType === 'hosting') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_price_id'] = $item->attributes->get('hosting_plan_price_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['linked_domain'] = $item->attributes->get('linked_domain');
                $metadata['is_existing_domain'] = $item->attributes->get('is_existing_domain');
                $metadata['duration_months'] = (int) ($item->attributes->get('duration_months') ?? $item->quantity);
            }

            if ($itemType === 'subscription_renewal') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_price_id'] = $item->attributes->get('hosting_plan_price_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['subscription_id'] = $item->attributes->get('subscription_id');
                $metadata['duration_months'] = (int) ($item->attributes->get('duration_months') ?? $item->quantity);
            }

            $years = match ($itemType) {
                'subscription_renewal', 'hosting' => (int) ($item->quantity / 12),
                'renewal', 'registration' => (int) $item->quantity,
                default => (int) $item->quantity,
            };

            $cartItems[] = [
                'domain_name' => $item->attributes->get('domain_name') ?? $item->name,
                'domain_type' => $itemType,
                'price' => $itemPrice,
                'currency' => $this->currency,
                'quantity' => $item->quantity,
                'years' => $years,
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
     * Format billing cycle duration for display in cart
     */
    public function formatBillingCycleDuration(?string $billingCycle): string
    {
        if (! $billingCycle) {
            return '1 Year';
        }

        $formatter = app(OrderItemFormatterService::class);
        $months = $this->getBillingCycleMonths($billingCycle);

        return $formatter->formatDurationLabel($months);
    }

    /**
     * Format duration in months to readable label
     */
    public function formatDurationLabel(int $months): string
    {
        $formatter = app(OrderItemFormatterService::class);

        return $formatter->formatDurationLabel($months);
    }

    public function getBillingCycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
            default => 1,
        };
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

    /**
     * Get the maximum registration years for a domain based on its TLD's domain price
     */
    private function getDomainMaxYears($cartItem): int
    {
        $domainName = $cartItem->attributes->get('domain_name') ?? $cartItem->name;

        if (! $domainName) {
            return 10; // Default max years
        }

        try {
            // Extract TLD from domain name
            $domainParts = explode('.', $domainName);
            if (count($domainParts) < 2) {
                return 10; // Default if TLD can't be extracted
            }

            $tld = '.'.end($domainParts);

            // Look up domain price for this TLD
            $domainPrice = DomainPrice::query()
                ->where('tld', $tld)
                ->where('status', 'active')
                ->first();

            if ($domainPrice && $domainPrice->max_years) {
                return (int) $domainPrice->max_years;
            }
        } catch (Exception $exception) {
            Log::warning('Failed to get domain max years', [
                'domain' => $domainName,
                'error' => $exception->getMessage(),
            ]);
        }

        return 10; // Default max years
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

    /**
     * Get billing cycle string from number of months
     */
    private function getBillingCycleFromMonths(int $months): string
    {
        return match (true) {
            $months === 1 => 'monthly',
            $months === 3 => 'quarterly',
            $months === 6 => 'semi-annually',
            $months === 12 => 'annually',
            $months === 24 => 'biennially',
            $months === 36 => 'triennially',
            default => 'monthly',
        };
    }
}
