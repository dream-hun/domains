<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use App\Services\Coupon\CouponService;
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

        // Calculate subtotal with currency conversion
        foreach ($cartContent as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $item->price;

            // Convert item price to display currency if different
            if ($itemCurrency !== $this->currency) {
                try {
                    $itemPrice = CurrencyHelper::convert(
                        $item->price,
                        $itemCurrency,
                        $this->currency
                    );
                } catch (Exception) {
                    // Fallback to original price if conversion fails
                    $itemPrice = $item->price;
                }
            }

            $itemTotal = $itemPrice * $item->quantity;
            $subtotal += $itemTotal;
        }

        $this->subtotal = $subtotal;

        // Discount is already set from session (applied in cart)
        // Just calculate total
        $this->total = max(0, $this->subtotal - $this->discount);
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
        $hasNewRegistrations = collect($this->cartItems)->contains(fn (array $item): bool => ($item['attributes']['type'] ?? 'registration') !== 'renewal');

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
        $itemPrice = $item['price'];

        // Convert item price to display currency if different
        if ($itemCurrency !== $this->currency) {
            try {
                $itemPrice = CurrencyHelper::convert(
                    $item['price'],
                    $itemCurrency,
                    $this->currency
                );
            } catch (Exception) {
                $itemPrice = $item['price'];
            }
        }

        try {
            return CurrencyHelper::formatMoney($itemPrice, $this->currency);
        } catch (Exception) {
            return $this->currency.' '.number_format($itemPrice, 2);
        }
    }

    /**
     * Get formatted total price for individual cart item
     */
    public function getFormattedItemTotal(array $item): string
    {
        $itemCurrency = $item['attributes']['currency'] ?? 'USD';
        $itemPrice = $item['price'];

        // Convert item price to display currency if different
        if ($itemCurrency !== $this->currency) {
            try {
                $itemPrice = CurrencyHelper::convert(
                    $item['price'],
                    $itemCurrency,
                    $this->currency
                );
            } catch (Exception) {
                $itemPrice = $item['price'];
            }
        }

        $total = $itemPrice * $item['quantity'];

        try {
            return CurrencyHelper::formatMoney($total, $this->currency);
        } catch (Exception) {
            return $this->currency.' '.number_format($total, 2);
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
            $itemPrice = $item->price;

            if ($itemCurrency !== $this->currency) {
                try {
                    $itemPrice = CurrencyHelper::convert(
                        $item->price,
                        $itemCurrency,
                        $this->currency
                    );
                } catch (Exception) {
                    $itemPrice = $item->price;
                }
            }

            $cartItems[] = [
                'domain_name' => $item->name,
                'domain_type' => $item->attributes->get('type', 'registration'),
                'price' => $itemPrice,
                'currency' => $this->currency,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'domain_id' => $item->attributes->get('domain_id'),
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
