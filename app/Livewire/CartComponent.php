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
use Illuminate\Support\Str;
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

        $subtotal = 0;

        try {
            foreach ($this->items as $item) {
                $itemCurrency = $item->attributes->currency ?? 'USD';
                $itemType = $item->attributes->get('type', 'registration');

                // Initialize duration_months and quantity for hosting items if not set
                if ($itemType === 'hosting' && ! $item->attributes->has('duration_months')) {
                    $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
                    $durationMonths = $this->getBillingCycleMonths($billingCycle);

                    // Calculate monthly price if not stored
                    $monthlyPrice = $item->attributes->get('monthly_unit_price');
                    if (! $monthlyPrice) {
                        $monthlyPrice = $durationMonths > 0 ? $item->price / $durationMonths : $item->price;
                    }

                    // Update the item to set duration_months, quantity, and monthly_unit_price
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

                    // Re-fetch the item to get updated values
                    $item = Cart::get($item->id);
                }

                if (! in_array($itemType, ['hosting', 'subscription_renewal'], true)) {
                    $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);
                    $itemTotal = $convertedPrice * $item->quantity;
                } elseif ($itemType === 'subscription_renewal') {
                    $monthlyPrice = $item->attributes->get('monthly_unit_price', $item->price);
                    $itemTotal = $monthlyPrice * $item->quantity;
                } else {
                    // For hosting, use monthly unit price if available, otherwise calculate from billing cycle
                    $monthlyPrice = $item->attributes->get('monthly_unit_price');
                    if (! $monthlyPrice) {
                        $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
                        $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
                        $monthlyPrice = $billingCycleMonths > 0 ? $item->price / $billingCycleMonths : $item->price;
                    }
                    $itemTotal = $monthlyPrice * $item->quantity;
                }

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
        $itemType = $item->attributes->get('type', 'registration');

        try {
            if (! in_array($itemType, ['hosting', 'subscription_renewal'], true)) {
                $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);

                return CurrencyHelper::formatMoney($convertedPrice, $this->currency);
            }

            if ($itemType === 'subscription_renewal') {
                $monthlyPrice = $item->attributes->get('monthly_unit_price', $item->price);

                return CurrencyHelper::formatMoney($monthlyPrice, $itemCurrency);
            }

            // For hosting, return monthly unit price
            $monthlyPrice = $item->attributes->get('monthly_unit_price');
            if (! $monthlyPrice) {
                $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
                $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
                $monthlyPrice = $billingCycleMonths > 0 ? $item->price / $billingCycleMonths : $item->price;
            }

            return CurrencyHelper::formatMoney($monthlyPrice, $itemCurrency);

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
        $itemType = $item->attributes->get('type', 'registration');

        try {
            if (! in_array($itemType, ['hosting', 'subscription_renewal'], true)) {
                $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);
                $total = $convertedPrice * $item->quantity;

                return CurrencyHelper::formatMoney($total, $this->currency);
            }

            if ($itemType === 'subscription_renewal') {
                $monthlyPrice = $item->attributes->get('monthly_unit_price', $item->price);
                $total = $monthlyPrice * $item->quantity;
            } else {
                // For hosting, use monthly unit price if available
                $monthlyPrice = $item->attributes->get('monthly_unit_price');
                if (! $monthlyPrice) {
                    $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
                    $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
                    $monthlyPrice = $billingCycleMonths > 0 ? $item->price / $billingCycleMonths : $item->price;
                }
                $total = $monthlyPrice * $item->quantity;
            }

            return CurrencyHelper::formatMoney($total, $itemCurrency);

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

            $itemType = $currentItem->attributes->get('type', 'registration');

            $maxQuantity = match ($itemType) {
                'subscription_renewal', 'hosting' => 36,
                default => 10,
            };

            if ($quantity >= 1 && $quantity <= $maxQuantity) {
                if (in_array($itemType, ['subscription_renewal', 'hosting'], true)) {
                    $existingAttributes = $currentItem->attributes->all();

                    // For hosting, calculate monthly price if not already stored
                    if ($itemType === 'hosting' && ! isset($existingAttributes['monthly_unit_price'])) {
                        $billingCycle = $existingAttributes['billing_cycle'] ?? 'monthly';
                        $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
                        $monthlyPrice = $billingCycleMonths > 0 ? $currentItem->price / $billingCycleMonths : $currentItem->price;
                        $existingAttributes['monthly_unit_price'] = $monthlyPrice;
                    }

                    // Update billing cycle based on new duration for hosting
                    if ($itemType === 'hosting') {
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
                    Cart::update($id, [
                        'quantity' => [
                            'relative' => false,
                            'value' => (int) $quantity,
                        ],
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
                $maxLabel = in_array($itemType, ['subscription_renewal', 'hosting'], true) ? '36 months' : '10 years';
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

    /**
     * Add subscription renewal to cart
     */
    public function addSubscriptionRenewalToCart(int $subscriptionId, string $billingCycle): void
    {
        try {
            $subscription = \App\Models\Subscription::with(['plan', 'planPrice'])->findOrFail($subscriptionId);
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

            $billingCycleEnum = \App\Enums\Hosting\BillingCycle::tryFrom($billingCycle);
            if (! $billingCycleEnum) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Invalid billing cycle selected',
                ]);

                return;
            }

            $monthlyPlanPrice = \App\Models\HostingPlanPrice::query()
                ->where('hosting_plan_id', $subscription->hosting_plan_id)
                ->where('billing_cycle', 'monthly')
                ->where('status', 'active')
                ->first();

            if (! $monthlyPlanPrice) {
                $monthlyPlanPrice = \App\Models\HostingPlanPrice::query()
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
                    'current_expiry' => $subscription->expires_at?->format('Y-m-d'),
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

            $durationLabel = $this->formatDurationLabel($durationMonths);
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

        foreach ($this->items as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemType = $item->attributes->get('type', 'registration');
            $itemPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);

            $metadata = $item->attributes->get('metadata', []);

            if ($itemType === 'hosting') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_price_id'] = $item->attributes->get('hosting_plan_price_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['linked_domain'] = $item->attributes->get('linked_domain');
                $metadata['is_existing_domain'] = $item->attributes->get('is_existing_domain');
            }

            if ($itemType === 'subscription_renewal') {
                $metadata['hosting_plan_id'] = $item->attributes->get('hosting_plan_id');
                $metadata['hosting_plan_price_id'] = $item->attributes->get('hosting_plan_price_id');
                $metadata['billing_cycle'] = $item->attributes->get('billing_cycle');
                $metadata['subscription_id'] = $item->attributes->get('subscription_id');
            }

            $cartItems[] = [
                'domain_name' => $item->attributes->get('domain_name') ?? $item->name,
                'domain_type' => $itemType,
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

    public function formatDurationLabel(int $months): string
    {
        if ($months < 12) {
            return $months.' '.Str::plural('month', $months);
        }

        $years = (int) ($months / 12);

        return $years.' '.Str::plural('year', $years);
    }

    /**
     * Format billing cycle duration for display in cart
     */
    public function formatBillingCycleDuration(?string $billingCycle): string
    {
        if (! $billingCycle) {
            return '1 Year';
        }

        $months = $this->getBillingCycleMonths($billingCycle);

        return $this->formatDurationLabel($months);
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
