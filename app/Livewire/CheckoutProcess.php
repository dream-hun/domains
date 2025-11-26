<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CheckoutProcess extends Component
{
    public $cartItems = [];

    public $subtotal = 0;

    public $discount = 0;

    public $total = 0;

    public $currency = 'USD';

    public $appliedCoupon;

    public $paymentMethod = 'stripe';

    public $isProcessing = false;

    public $errorMessage = '';

    public $successMessage = '';

    // Contact selection properties
    public $userContacts = [];

    public $selectedContactId;

    public $showContactSelection = false;

    protected $listeners = [
        'cartUpdated' => 'refreshCart',
        'currencyChanged' => 'updateCurrency',
        'couponApplied' => 'refreshCart',
        'couponRemoved' => 'refreshCart',
    ];

    public function mount(): void
    {
        $this->currency = CurrencyHelper::getUserCurrency();
        $this->refreshCart();
        $this->loadUserContacts();
        $this->restoreCouponFromSession();
    }

    public function updateCurrency(string $currency): void
    {
        $this->currency = $currency;
        $this->refreshCart();
    }

    public function refreshCart(): void
    {
        $cartContent = Cart::getContent();
        $this->cartItems = $cartContent->toArray();
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $cartContent = Cart::getContent();
        $subtotal = 0;

        try {
            foreach ($cartContent as $item) {
                $itemCurrency = $item->attributes->currency ?? 'USD';
                $convertedPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);
                $itemTotal = $convertedPrice * $item->quantity;
                $subtotal += $itemTotal;
            }

            $this->subtotal = $subtotal;
            $this->total = max(0, $this->subtotal - $this->discount);

            if ($this->errorMessage === 'We were unable to convert your cart totals. Please try again or switch currencies.') {
                $this->errorMessage = '';
            }
        } catch (Exception $exception) {
            Log::error('Failed to calculate checkout totals', [
                'currency' => $this->currency,
                'error' => $exception->getMessage(),
            ]);

            $this->subtotal = 0;
            $this->total = 0;
            $this->errorMessage = 'We were unable to convert your cart totals. Please try again or switch currencies.';
        }
    }

    public function loadUserContacts(): void
    {
        $this->userContacts = auth()->user()->contacts()->get()->toArray();

        // Auto-select primary contact if available
        $primaryContact = collect($this->userContacts)->firstWhere('is_primary', true);
        if ($primaryContact) {
            $this->selectedContactId = $primaryContact['id'];
        } elseif (! empty($this->userContacts)) {
            // Select first contact if no primary contact
            $this->selectedContactId = $this->userContacts[0]['id'];
        }
    }

    public function selectContact($contactId): void
    {
        $this->selectedContactId = $contactId;
        $this->showContactSelection = false;
        $this->successMessage = 'Contact selected successfully.';
    }

    public function toggleContactSelection(): void
    {
        $this->showContactSelection = ! $this->showContactSelection;
    }

    public function proceedToPayment()
    {
        if (empty($this->cartItems)) {
            $this->errorMessage = 'Your cart is empty.';

            return null;
        }

        if ($this->total <= 0) {
            $this->errorMessage = 'Invalid order total.';

            return null;
        }

        // Check if cart has any new domain registrations (not renewals)
        $hasNewRegistrations = collect($this->cartItems)->contains(
            fn (array $item): bool => ($item['attributes']['type'] ?? 'registration') === 'registration'
        );

        // Validate contact selection only if cart has new registrations
        if ($hasNewRegistrations && ! $this->selectedContactId) {
            $this->errorMessage = 'Please select a contact for domain registration.';
            $this->showContactSelection = true;

            return null;
        }

        try {
            $this->isProcessing = true;

            // Prepare cart data with currency conversion
            $preparedCartData = $this->prepareCartForPayment();

            // Store prepared cart data in session
            session([
                'cart' => $preparedCartData['items'],
                'cart_subtotal' => $preparedCartData['subtotal'],
                'cart_total' => $preparedCartData['total'],
                'selected_currency' => $preparedCartData['currency'],
            ]);

            // Store coupon data if present
            if (isset($preparedCartData['coupon'])) {
                session(['coupon' => $preparedCartData['coupon']]);
            }

            // Store checkout data
            session([
                'checkout' => [
                    'payment_method' => $this->paymentMethod,
                    'total' => $preparedCartData['total'],
                    'currency' => $preparedCartData['currency'],
                    'selected_contact_id' => $this->selectedContactId,
                    'created_at' => now()->toISOString(),
                ],
            ]);

            Log::info('Checkout process completed', [
                'payment_method' => $this->paymentMethod,
                'total' => $preparedCartData['total'],
                'currency' => $preparedCartData['currency'],
                'contact_id' => $this->selectedContactId,
            ]);

            // Redirect to payment page
            return to_route('payment.index');

        } catch (Exception $exception) {
            $this->errorMessage = 'Failed to proceed to payment: '.$exception->getMessage();
            Log::error('Checkout process failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        } finally {
            $this->isProcessing = false;
        }

        return null;
    }

    public function render(): Factory|View
    {
        return view('livewire.checkout-process');
    }

    /**
     * Get formatted subtotal with currency
     */
    #[Computed]
    public function formattedSubtotal(): string
    {
        try {
            return CurrencyHelper::formatMoney($this->subtotal, $this->currency);
        } catch (Exception) {
            return $this->currency.' '.number_format($this->subtotal, 2);
        }
    }

    /**
     * Get formatted total with currency
     */
    #[Computed]
    public function formattedTotal(): string
    {
        try {
            return CurrencyHelper::formatMoney($this->total, $this->currency);
        } catch (Exception) {
            return $this->currency.' '.number_format($this->total, 2);
        }
    }

    /**
     * Get formatted discount with currency
     */
    #[Computed]
    public function formattedDiscount(): string
    {
        try {
            return CurrencyHelper::formatMoney($this->discount, $this->currency);
        } catch (Exception) {
            return $this->currency.' '.number_format($this->discount, 2);
        }
    }

    /**
     * Get formatted price for individual cart item
     */
    public function getFormattedItemPrice(array $item): string
    {
        $itemCurrency = $item['attributes']['currency'] ?? 'USD';
        try {
            $convertedPrice = $this->convertToDisplayCurrency($item['price'], $itemCurrency);

            return CurrencyHelper::formatMoney($convertedPrice, $this->currency);
        } catch (Exception $exception) {
            Log::warning('Falling back to original currency for item price display', [
                'currency' => $this->currency,
                'item_currency' => $itemCurrency,
                'error' => $exception->getMessage(),
            ]);

            try {
                return CurrencyHelper::formatMoney($item['price'], $itemCurrency);
            } catch (Exception) {
                return $itemCurrency.' '.number_format($item['price'], 2);
            }
        }
    }

    /**
     * Get formatted total price for individual cart item
     */
    public function getFormattedItemTotal(array $item): string
    {
        $itemCurrency = $item['attributes']['currency'] ?? 'USD';
        try {
            $convertedPrice = $this->convertToDisplayCurrency($item['price'], $itemCurrency);
            $total = $convertedPrice * $item['quantity'];

            return CurrencyHelper::formatMoney($total, $this->currency);
        } catch (Exception $exception) {
            Log::warning('Falling back to original currency for item total display', [
                'currency' => $this->currency,
                'item_currency' => $itemCurrency,
                'error' => $exception->getMessage(),
            ]);

            $total = $item['price'] * $item['quantity'];

            try {
                return CurrencyHelper::formatMoney($total, $itemCurrency);
            } catch (Exception) {
                return $itemCurrency.' '.number_format($total, 2);
            }
        }
    }

    /**
     * Prepare cart data for payment processing
     */
    private function prepareCartForPayment(): array
    {
        $cartItems = [];
        $cartContent = Cart::getContent();

        foreach ($cartContent as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $this->convertToDisplayCurrency($item->price, $itemCurrency);

            $cartItems[] = [
                'domain_name' => $item->attributes->get('domain_name') ?? $item->name,
                'domain_type' => $item->attributes->get('type', 'registration'),
                'price' => $itemPrice,
                'currency' => $this->currency,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'domain_id' => $item->attributes->get('domain_id'),
                'metadata' => $item->attributes->get('metadata', []),
                'hosting_plan_id' => $item->attributes->get('hosting_plan_id'),
                'hosting_plan_price_id' => $item->attributes->get('hosting_plan_price_id'),
                'linked_domain' => $item->attributes->get('linked_domain'),
            ];
        }

        $paymentData = [
            'items' => $cartItems,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'currency' => $this->currency,
        ];

        if ($this->appliedCoupon) {
            $paymentData['coupon'] = [
                'code' => $this->appliedCoupon->code,
                'type' => $this->appliedCoupon->type->value,
                'value' => $this->appliedCoupon->value,
                'discount_amount' => $this->discount,
            ];
        }

        return $paymentData;
    }

    /**
     * Convert an amount from its original currency into the current display currency.
     *
     * @throws Exception
     */
    private function convertToDisplayCurrency(float $amount, string $fromCurrency): float
    {
        $fromCurrency = mb_strtoupper($fromCurrency);
        $toCurrency = mb_strtoupper((string) $this->currency);

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        try {
            return CurrencyHelper::convert($amount, $fromCurrency, $toCurrency);
        } catch (Exception $exception) {
            Log::warning('Primary currency conversion failed', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'amount' => $amount,
                'error' => $exception->getMessage(),
            ]);

            try {
                $currencyService = app(CurrencyService::class);

                return $currencyService->convert($amount, $fromCurrency, $toCurrency);
            } catch (Exception $fallbackException) {
                Log::error('Currency conversion failed after fallback', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'amount' => $amount,
                    'error' => $fallbackException->getMessage(),
                ]);

                throw new Exception('Unable to convert currency.', $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Restore coupon from session if exists (read-only, for display purposes)
     */
    private function restoreCouponFromSession(): void
    {
        if (session()->has('coupon')) {
            $couponData = session('coupon');

            // Just store the coupon data for display, don't recalculate
            // The discount is already calculated in the cart
            $this->discount = $couponData['discount_amount'] ?? 0;

            // Try to load the coupon model for display
            try {
                $couponService = new CouponService;
                $this->appliedCoupon = $couponService->validateCoupon($couponData['code']);
            } catch (Exception $e) {
                // Coupon is no longer valid, but keep the discount from session
                // since it was already applied in the cart
                Log::warning('Stored coupon is no longer valid but keeping discount', [
                    'coupon_code' => $couponData['code'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
