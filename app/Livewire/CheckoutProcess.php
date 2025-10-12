<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Contact;
use App\Services\Coupon\CouponService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Livewire\Component;

final class CheckoutProcess extends Component
{
    public $cartItems = [];

    public $subtotal = 0;

    public $discount = 0;

    public $total = 0;

    public $couponCode = '';

    public $appliedCoupon = null;

    public $paymentMethod = 'stripe';

    public $isProcessing = false;

    public $errorMessage = '';

    public $successMessage = '';

    // Contact selection properties
    public $userContacts = [];

    public $selectedContactId = null;

    public $showContactSelection = false;

    protected $listeners = ['cartUpdated' => 'refreshCart'];

    public function mount(): void
    {
        $this->refreshCart();
        $this->loadUserContacts();
    }

    public function refreshCart(): void
    {
        $this->cartItems = Cart::getContent()->toArray();
        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = Cart::getContent()->sum(fn ($item) => $item->price * $item->quantity);
        $this->total = $this->subtotal - $this->discount;
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

    public function applyCoupon(): void
    {
        if (empty($this->couponCode)) {
            $this->errorMessage = 'Please enter a coupon code.';

            return;
        }

        try {
            $this->isProcessing = true;
            $this->errorMessage = '';

            $couponService = new CouponService();
            $coupon = $couponService->validateCoupon($this->couponCode);

            // Calculate discount amount
            if ($coupon->type->value === 'fixed') {
                $this->discount = min($coupon->value, $this->subtotal);
            } elseif ($coupon->type->value === 'percentage') {
                $this->discount = $this->subtotal * ($coupon->value / 100);
            }

            $this->appliedCoupon = $coupon;
            $this->calculateTotals();
            $this->successMessage = "Coupon '{$coupon->code}' applied successfully!";
            $this->couponCode = '';

        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function removeCoupon(): void
    {
        $this->appliedCoupon = null;
        $this->discount = 0;
        $this->calculateTotals();
        $this->successMessage = 'Coupon removed.';
    }

    public function proceedToPayment()
    {
        if (empty($this->cartItems)) {
            $this->errorMessage = 'Your cart is empty.';

            return;
        }

        if ($this->total <= 0) {
            $this->errorMessage = 'Invalid order total.';

            return;
        }

        // Validate contact selection
        if (! $this->selectedContactId) {
            $this->errorMessage = 'Please select a contact for domain registration.';
            $this->showContactSelection = true;

            return;
        }

        try {
            $this->isProcessing = true;

            // Store checkout data in session
            session([
                'checkout' => [
                    'payment_method' => $this->paymentMethod,
                    'coupon_code' => $this->appliedCoupon?->code,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'cart_items' => $this->cartItems,
                    'selected_contact_id' => $this->selectedContactId,
                    'created_at' => now()->toISOString(),
                ],
            ]);

            // Redirect to payment page
            return redirect()->route('payment.index');

        } catch (Exception $e) {
            $this->errorMessage = 'Failed to proceed to payment: '.$e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        return view('livewire.checkout-process');
    }
}
