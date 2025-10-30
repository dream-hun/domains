<?php

declare(strict_types=1);

namespace App\Livewire\Checkout;

use App\Helpers\CurrencyHelper;
use App\Models\Contact;
use App\Models\Coupon;
use App\Services\CheckoutService;
use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class CheckoutWizard extends Component
{
    public const STEP_REVIEW = 1;

    public const STEP_CONTACT = 2;

    public const STEP_PAYMENT = 3;

    public const STEP_CONFIRMATION = 4;

    public int $currentStep = 1;

    public ?int $selectedRegistrantId = null;

    public ?int $selectedAdminId = null;

    public ?int $selectedTechId = null;

    public ?int $selectedBillingId = null;

    public ?string $selectedPaymentMethod = null;

    public ?string $orderNumber = null;

    public bool $isProcessing = false;

    public string $errorMessage = '';

    public array $paymentMethods = [];

    public string $userCurrencyCode = 'USD';

    public ?Coupon $appliedCoupon = null;

    public float $discountAmount = 0;

    public bool $isCouponApplied = false;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function mount(CurrencyService $currencyService): void
    {

        if (Cart::isEmpty()) {
            $this->redirect(route('cart.index'), navigate: true);

            return;
        }

        $currency = $currencyService->getUserCurrency();
        $this->userCurrencyCode = $currency->code;

        $defaultContact = auth()->user()->contacts()
            ->where('is_primary', true)
            ->first();

        if ($defaultContact) {
            $this->selectedRegistrantId = $defaultContact->id;
            $this->selectedAdminId = $defaultContact->id;
            $this->selectedTechId = $defaultContact->id;
            $this->selectedBillingId = $defaultContact->id;
        }

        // Restore coupon from session if exists
        if (session()->has('coupon')) {
            $couponData = session('coupon');
            $this->appliedCoupon = Coupon::where('code', $couponData['code'])->first();
            if ($this->appliedCoupon) {
                $this->isCouponApplied = true;
                $this->calculateDiscount();
            }
        }

        $this->initializePaymentMethods();

        $this->restoreCheckoutState();
    }

    #[Computed(persist: false)]
    public function cartItems()
    {
        return Cart::getContent();
    }

    #[Computed(persist: false)]
    public function userContacts()
    {
        return auth()->user()->contacts()->get();
    }

    #[Computed(persist: false)]
    public function selectedRegistrant(): ?Contact
    {
        if (! $this->selectedRegistrantId) {
            return null;
        }

        return Contact::find($this->selectedRegistrantId);
    }

    #[Computed(persist: false)]
    public function selectedAdmin(): ?Contact
    {
        if (! $this->selectedAdminId) {
            return null;
        }

        return Contact::find($this->selectedAdminId);
    }

    #[Computed(persist: false)]
    public function selectedTech(): ?Contact
    {
        if (! $this->selectedTechId) {
            return null;
        }

        return Contact::find($this->selectedTechId);
    }

    #[Computed(persist: false)]
    public function selectedBilling(): ?Contact
    {
        if (! $this->selectedBillingId) {
            return null;
        }

        return Contact::find($this->selectedBillingId);
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function orderTotal(): float
    {
        // Calculate total with currency conversion
        $total = 0;
        foreach ($this->cartItems as $item) {
            $itemPrice = $item->getPriceSum();
            $itemCurrency = $item->attributes->currency ?? 'USD';

            // Convert to user's currency if different
            if ($itemCurrency !== $this->userCurrencyCode) {
                $itemPrice = CurrencyHelper::convert($itemPrice, $itemCurrency, $this->userCurrencyCode);
            }

            $total += $itemPrice;
        }

        // Subtract discount if coupon is applied
        return max(0, $total - $this->discountAmount);
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function orderSubtotal(): float
    {
        // Calculate subtotal with currency conversion
        $subtotal = 0;
        foreach ($this->cartItems as $item) {
            $itemPrice = $item->getPriceSum();
            $itemCurrency = $item->attributes->currency ?? 'USD';

            // Convert to user's currency if different
            if ($itemCurrency !== $this->userCurrencyCode) {
                $itemPrice = CurrencyHelper::convert($itemPrice, $itemCurrency, $this->userCurrencyCode);
            }

            $subtotal += $itemPrice;
        }

        return $subtotal;
    }

    // Helper method to format currency - always uses user's selected currency

    /**
     * @throws Exception
     */
    public function formatCurrency(float $amount): string
    {
        return CurrencyHelper::formatMoney($amount, $this->userCurrencyCode);
    }

    // Helper method to get item price - converts and formats in user's currency

    /**
     * @throws Exception
     */
    public function getItemPrice($item): string
    {
        $itemPrice = $item->getPriceSum();
        $itemCurrency = $item->attributes->currency ?? 'USD';

        // Convert to user's currency if different
        if ($itemCurrency !== $this->userCurrencyCode) {
            $itemPrice = CurrencyHelper::convert($itemPrice, $itemCurrency, $this->userCurrencyCode);
        }

        return CurrencyHelper::formatMoney($itemPrice, $this->userCurrencyCode);
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 4) {
            return;
        }

        if ($step > $this->currentStep && ! $this->validateCurrentStep()) {
            return;
        }

        $this->currentStep = $step;
        $this->saveCheckoutState();
    }

    public function nextStep(): void
    {
        if (! $this->validateCurrentStep()) {
            return;
        }

        $this->currentStep++;
        $this->saveCheckoutState();
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->saveCheckoutState();
        }
    }

    public function selectRegistrant(int $contactId): void
    {
        $this->selectedRegistrantId = $contactId;
        $this->errorMessage = '';
    }

    public function selectAdmin(int $contactId): void
    {
        $this->selectedAdminId = $contactId;
        $this->errorMessage = '';
    }

    public function selectTech(int $contactId): void
    {
        $this->selectedTechId = $contactId;
        $this->errorMessage = '';
    }

    public function selectBilling(int $contactId): void
    {
        $this->selectedBillingId = $contactId;
        $this->errorMessage = '';
    }

    public function useContactForAll(int $contactId): void
    {
        $this->selectedRegistrantId = $contactId;
        $this->selectedAdminId = $contactId;
        $this->selectedTechId = $contactId;
        $this->selectedBillingId = $contactId;
        $this->errorMessage = '';
    }

    public function createNewContact(): void
    {
        $this->dispatch('open-contact-modal');
    }

    public function contactCreated(int $contactId): void
    {
        $this->useContactForAll($contactId);
        $this->dispatch('close-contact-modal');
    }

    public function selectPaymentMethod(string $method): void
    {
        $this->selectedPaymentMethod = $method;
        $this->errorMessage = '';
        $this->dispatch('payment-method-selected', $method);
    }

    public function completeOrder()
    {
        if (! $this->validateCurrentStep()) {
            return back();
        }

        $this->isProcessing = true;

        try {
            $checkoutService = app(CheckoutService::class);

            $order = $checkoutService->processCheckout([
                'user_id' => auth()->id(),
                'contact_ids' => [
                    'registrant' => $this->selectedRegistrantId,
                    'admin' => $this->selectedAdminId,
                    'tech' => $this->selectedTechId,
                    'billing' => $this->selectedBillingId,
                ],
                'payment_method' => $this->selectedPaymentMethod,
                'currency' => $this->userCurrencyCode,
                'cart_items' => $this->cartItems,
                'coupon' => $this->appliedCoupon,
                'discount_amount' => $this->discountAmount,
            ]);

            // Check if we need to redirect to Stripe Checkout
            if ($this->selectedPaymentMethod === 'stripe' && $order->stripe_session_id) {
                // Redirect to Stripe Checkout
                return redirect()->route('checkout.stripe.redirect', ['order' => $order->order_number]);
            }


            $this->orderNumber = $order->order_number;
            $this->currentStep = self::STEP_CONFIRMATION;

            Cart::clear();
            $this->clearCheckoutState();
        } catch (Exception $e) {
            $this->errorMessage = 'Payment processing failed. Please try again.';
            logger()->error('Checkout failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render(): Factory|View
    {
        return view('livewire.checkout.checkout-wizard');
    }

    // Validation
    private function validateCurrentStep(): bool
    {
        $this->errorMessage = '';

        return match ($this->currentStep) {
            self::STEP_REVIEW => $this->validateReviewStep(),
            self::STEP_CONTACT => $this->validateContactStep(),
            self::STEP_PAYMENT => $this->validatePaymentStep(),
            default => true,
        };
    }

    private function validateReviewStep(): bool
    {
        if (Cart::isEmpty()) {
            $this->errorMessage = 'Your cart is empty.';

            return false;
        }

        return true;
    }

    private function validateContactStep(): bool
    {
        if (! $this->selectedRegistrantId || ! $this->selectedAdminId || ! $this->selectedTechId || ! $this->selectedBillingId) {
            $this->errorMessage = 'Please select contacts for all roles (Registrant, Admin, Technical, and Billing).';

            return false;
        }

        return true;
    }

    private function validatePaymentStep(): bool
    {
        if (! $this->selectedPaymentMethod) {
            $this->errorMessage = 'Please select a payment method.';

            return false;
        }

        return true;
    }

    // State management
    private function saveCheckoutState(): void
    {
        session()->put('checkout_state', [
            'current_step' => $this->currentStep,
            'selected_registrant_id' => $this->selectedRegistrantId,
            'selected_admin_id' => $this->selectedAdminId,
            'selected_tech_id' => $this->selectedTechId,
            'selected_billing_id' => $this->selectedBillingId,
            'selected_payment_method' => $this->selectedPaymentMethod,
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function restoreCheckoutState(): void
    {
        $state = session()->get('checkout_state');

        if ($state) {
            $this->currentStep = $state['current_step'] ?? 1;
            $this->selectedRegistrantId = $state['selected_registrant_id'] ?? null;
            $this->selectedAdminId = $state['selected_admin_id'] ?? null;
            $this->selectedTechId = $state['selected_tech_id'] ?? null;
            $this->selectedBillingId = $state['selected_billing_id'] ?? null;
            $this->selectedPaymentMethod = $state['selected_payment_method'] ?? null;
        }
    }

    private function clearCheckoutState(): void
    {
        session()->forget('checkout_state');
    }

    private function initializePaymentMethods(): void
    {
        $this->paymentMethods = [];

        // Stripe
        if (config('services.payment.stripe.publishable_key')) {
            $this->paymentMethods[] = [
                'id' => 'stripe',
                'name' => 'Credit Card (Stripe)',
            ];
        }

        // PayPal
        if (config('services.paypal.client_id')) {
            $this->paymentMethods[] = [
                'id' => 'paypal',
                'name' => 'PayPal',
            ];
        }
    }

    private function calculateDiscount(): void
    {
        if (! $this->appliedCoupon || ! $this->isCouponApplied) {
            $this->discountAmount = 0;

            return;
        }

        $subtotal = $this->orderSubtotal;

        $couponService = app(CouponService::class);
        $discountedTotal = $couponService->applyCoupon($this->appliedCoupon, $subtotal);

        $discount = $subtotal - $discountedTotal;

        $this->discountAmount = max(0, min($discount, $subtotal));
    }
}
