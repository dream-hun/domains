<?php

declare(strict_types=1);

namespace App\Livewire\Checkout;

use App\Models\Contact;
use App\Services\CheckoutService;
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

    public ?int $selectedContactId = null;

    public bool $useContactForAll = true;

    public ?string $selectedPaymentMethod = null;

    public ?string $orderNumber = null;

    public bool $isProcessing = false;

    public string $errorMessage = '';

    public array $paymentMethods = [];

    public string $userCurrencyCode = 'USD';

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
            $this->selectedContactId = $defaultContact->id;
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
    public function selectedContact(): Contact|array|null
    {
        if (! $this->selectedContactId) {
            return null;
        }

        return Contact::find($this->selectedContactId);
    }

    #[Computed]
    public function orderTotal(): float
    {
        // Cart totals are already in the correct currency
        // No conversion needed as items are stored with their currency attribute
        return (float) Cart::getTotal();
    }

    #[Computed]
    public function orderSubtotal(): float
    {
        // Cart subtotals are already in the correct currency
        // No conversion needed as items are stored with their currency attribute
        return (float) Cart::getSubTotal();
    }

    // Helper method to format currency
    public function formatCurrency(float $amount): string
    {
        $currencyService = app(CurrencyService::class);

        return $currencyService->format($amount, $this->userCurrencyCode);
    }

    // Helper method to get item price (already in correct currency)
    public function getItemPrice($item): string
    {
        $currencyService = app(CurrencyService::class);

        // Item prices are already in the correct currency from cart
        $itemCurrency = $item->attributes->currency ?? 'USD';

        return $currencyService->format($item->getPriceSum(), $itemCurrency);
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

    public function selectContact(int $contactId): void
    {
        $this->selectedContactId = $contactId;
        $this->errorMessage = '';
    }

    public function createNewContact(): void
    {
        $this->dispatch('open-contact-modal');
    }

    public function contactCreated(int $contactId): void
    {
        $this->selectedContactId = $contactId;
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
            return;
        }

        $this->isProcessing = true;

        try {
            $checkoutService = app(CheckoutService::class);

            $order = $checkoutService->processCheckout([
                'user_id' => auth()->id(),
                'contact_id' => $this->selectedContactId,
                'use_contact_for_all' => $this->useContactForAll,
                'payment_method' => $this->selectedPaymentMethod,
                'currency' => $this->userCurrencyCode,
                'cart_items' => $this->cartItems,
            ]);

            // Check if we need to redirect to Stripe Checkout
            if ($this->selectedPaymentMethod === 'stripe' && $order->stripe_session_id) {
                // Redirect to Stripe Checkout
                return redirect()->route('checkout.stripe.redirect', ['order' => $order->order_number]);
            }

            // Payment completed (e.g., account credit)
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
        if (! $this->selectedContactId) {
            $this->errorMessage = 'Please select a contact or create a new one.';

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
            'selected_contact_id' => $this->selectedContactId,
            'use_contact_for_all' => $this->useContactForAll,
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
            $this->selectedContactId = $state['selected_contact_id'] ?? null;
            $this->useContactForAll = $state['use_contact_for_all'] ?? true;
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

        // Account Credit
        $user = auth()->user();
        if ($user && $user->account_credit > 0) {
            $this->paymentMethods[] = [
                'id' => 'account_credit',
                'name' => 'Account Credit ('.number_format($user->account_credit, 2).' available)',
                'balance' => $user->account_credit,
            ];
        }

        // Stripe
        if (config('cashier.key')) {
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
}
