<?php

declare(strict_types=1);

namespace App\Livewire\Checkout;

use App\Helpers\CurrencyHelper;
use App\Models\Contact;
use App\Models\Coupon;
use App\Services\CartPriceConverter;
use App\Services\CheckoutService;
use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use App\Services\OrderItemFormatterService;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * @property-read CartCollection $cartItems
 * @property-read bool $hasOnlyRenewals
 * @property-read bool $hasItemsRequiringContacts
 * @property-read Collection $userContacts
 * @property-read ?Contact $selectedRegistrant
 * @property-read ?Contact $selectedAdmin
 * @property-read ?Contact $selectedTech
 * @property-read ?Contact $selectedBilling
 * @property-read float $orderTotal
 * @property-read float $orderSubtotal
 */
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

    public ?int $quickSelectContactId = null;

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

        /** @var Contact|null $defaultContact */
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
            $this->appliedCoupon = Coupon::query()->where('code', $couponData['code'])->first();
            if ($this->appliedCoupon instanceof Coupon) {
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
    public function hasOnlyRenewals(): bool
    {
        $cartItems = $this->cartItems;

        if ($cartItems->isEmpty()) {
            return false;
        }

        return $cartItems->every(fn ($item): bool => ($item->attributes->type ?? 'registration') === 'renewal');
    }

    #[Computed(persist: false)]
    public function hasItemsRequiringContacts(): bool
    {
        $cartItems = $this->cartItems;

        if ($cartItems->isEmpty()) {
            return false;
        }

        return $cartItems->contains(function ($item): bool {
            $itemType = $item->attributes->type ?? 'registration';

            if (in_array($itemType, ['domain', 'registration', 'transfer'], true)) {
                return true;
            }

            if ($itemType === 'hosting') {
                return $item->attributes->domain_required ?? false;
            }

            return false;
        });
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

        return Contact::query()->find($this->selectedRegistrantId);
    }

    #[Computed(persist: false)]
    public function selectedAdmin(): ?Contact
    {
        if (! $this->selectedAdminId) {
            return null;
        }

        return Contact::query()->find($this->selectedAdminId);
    }

    #[Computed(persist: false)]
    public function selectedTech(): ?Contact
    {
        if (! $this->selectedTechId) {
            return null;
        }

        return Contact::query()->find($this->selectedTechId);
    }

    #[Computed(persist: false)]
    public function selectedBilling(): ?Contact
    {
        if (! $this->selectedBillingId) {
            return null;
        }

        return Contact::query()->find($this->selectedBillingId);
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function orderTotal(): float
    {
        $cartPriceConverter = resolve(CartPriceConverter::class);
        $subtotal = $cartPriceConverter->calculateCartSubtotal($this->cartItems, $this->userCurrencyCode);

        return max(0, $subtotal - $this->discountAmount);
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function orderSubtotal(): float
    {
        $cartPriceConverter = resolve(CartPriceConverter::class);

        return $cartPriceConverter->calculateCartSubtotal($this->cartItems, $this->userCurrencyCode);
    }

    /**
     * @throws Exception
     */
    public function formatCurrency(float $amount): string
    {
        return CurrencyHelper::formatMoney($amount, $this->userCurrencyCode);
    }

    /**
     * @throws Exception
     */
    public function getItemPrice(object $item): string
    {
        $cartPriceConverter = resolve(CartPriceConverter::class);
        $itemTotal = $cartPriceConverter->calculateItemTotal($item, $this->userCurrencyCode);

        return CurrencyHelper::formatMoney($itemTotal, $this->userCurrencyCode);
    }

    /**
     * Get formatted registration period for display
     */
    public function getRegistrationPeriod(object $item): string
    {
        $formatter = resolve(OrderItemFormatterService::class);

        return $formatter->getCartItemPeriod($item);
    }

    /**
     * Get unit price per billing cycle for display
     *
     * @throws Exception
     */
    public function getItemUnitPrice($item): string
    {
        $item->attributes->get('type', 'registration');
        $itemCurrency = $item->attributes->get('currency', 'USD');
        $unitPrice = $item->price;

        return CurrencyHelper::formatMoney($unitPrice, $itemCurrency);

    }

    /**
     * Get display name for cart item (plan name only for subscription renewals and hosting)
     */
    public function getItemDisplayName(object $item): string
    {
        $formatter = resolve(OrderItemFormatterService::class);

        return $formatter->getCartItemDisplayName($item);
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

        if ($this->currentStep === self::STEP_REVIEW && (! $this->hasItemsRequiringContacts || $this->hasOnlyRenewals)) {
            $this->currentStep = self::STEP_PAYMENT;
        } else {
            $this->currentStep++;
        }

        $this->saveCheckoutState();
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {

            if ($this->currentStep === self::STEP_PAYMENT && (! $this->hasItemsRequiringContacts || $this->hasOnlyRenewals)) {
                $this->currentStep = self::STEP_REVIEW;
            } else {
                $this->currentStep--;
            }

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

    public function updatedQuickSelectContactId(?int $contactId): void
    {
        if ($contactId) {
            $this->useContactForAll($contactId);
        }
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

    public function completeOrder(): ?RedirectResponse
    {
        if (! $this->validateCurrentStep()) {
            return back();
        }

        $this->isProcessing = true;

        try {
            $checkoutService = resolve(CheckoutService::class);

            $billingContactId = $this->selectedBillingId;
            if ((! $this->hasItemsRequiringContacts || $this->hasOnlyRenewals) && ! $billingContactId) {
                /** @var Contact|null $primaryContact */
                $primaryContact = auth()->user()->contacts()->where('is_primary', true)->first();
                $billingContactId = $primaryContact?->id;
            }

            $orderCurrency = $this->userCurrencyCode;
            $convertedCartItems = $this->convertCartItemsCurrency($this->cartItems, $orderCurrency);

            $order = $checkoutService->processCheckout([
                'user_id' => auth()->id(),
                'contact_ids' => [
                    'registrant' => $this->selectedRegistrantId,
                    'admin' => $this->selectedAdminId,
                    'tech' => $this->selectedTechId,
                    'billing' => $billingContactId,
                ],
                'payment_method' => $this->selectedPaymentMethod,
                'currency' => $orderCurrency,
                'cart_items' => $convertedCartItems,
                'coupon' => $this->appliedCoupon,
                'discount_amount' => $this->discountAmount,
            ]);

            if ($this->selectedPaymentMethod === 'stripe' && $order->stripe_session_id) {
                return to_route('checkout.stripe.redirect', ['order' => $order->order_number]);
            }

            $this->orderNumber = $order->order_number;
            $this->currentStep = self::STEP_CONFIRMATION;

            Cart::clear();
            $this->clearCheckoutState();
        } catch (Exception $exception) {
            $this->errorMessage = 'Payment processing failed. Please try again.';
            logger()->error('Checkout failed', [
                'user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
        } finally {
            $this->isProcessing = false;
        }

        return null;
    }

    public function render(): Factory|View
    {
        return view('livewire.checkout.checkout-wizard');
    }

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

        if ($this->hasOnlyRenewals || ! $this->hasItemsRequiringContacts) {
            return true;
        }

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
            'quick_select_contact_id' => $this->quickSelectContactId,
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
            $this->quickSelectContactId = $state['quick_select_contact_id'] ?? null;
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

        $couponService = resolve(CouponService::class);
        $discountedTotal = $couponService->applyCoupon($this->appliedCoupon, $subtotal);

        $discount = $subtotal - $discountedTotal;

        $this->discountAmount = max(0, min($discount, $subtotal));
    }

    /**
     * Convert cart items' prices to the target currency
     *
     * @throws Exception
     */
    private function convertCartItemsCurrency(CartCollection $cartItems, string $targetCurrency): CartCollection
    {
        $cartPriceConverter = resolve(CartPriceConverter::class);

        try {
            return $cartPriceConverter->convertCartItemsToCurrency($cartItems, $targetCurrency);
        } catch (Exception $exception) {
            logger()->error('Failed to convert cart items currency in checkout', [
                'to' => $targetCurrency,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
