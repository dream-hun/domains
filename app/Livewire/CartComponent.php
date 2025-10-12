<?php

declare(strict_types=1);

namespace App\Livewire;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CartComponent extends Component
{
    public $items;

    public $subtotalAmount = 0;

    public $totalAmount = 0;

    public $currency;

    protected $listeners = ['refreshCart' => '$refresh', 'currencyChanged' => 'updateCurrency'];

    public function mount(): void
    {
        $this->currency = \App\Helpers\CurrencyHelper::getUserCurrency();
        $this->updateCartTotal();
    }

    public function updateCurrency($newCurrency): void
    {
        $this->currency = $newCurrency;
        $this->updateCartTotal();
    }

    public function updateCartTotal(): void
    {
        // Get cart content and maintain original order
        $cartContent = Cart::getContent();

        // Sort by creation timestamp to maintain consistent order
        // This ensures items stay in the same order regardless of updates
        $this->items = $cartContent->sortBy(function ($item) {
            return $item->attributes->get('added_at', 0);
        });

        // Calculate totals with currency conversion
        $subtotal = 0;
        $total = 0;

        foreach ($this->items as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $item->price;

            // Convert item price to display currency if different
            if ($itemCurrency !== $this->currency) {
                try {
                    $itemPrice = \App\Helpers\CurrencyHelper::convert(
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
            $total += $itemTotal; // For now, subtotal and total are the same
        }

        $this->subtotalAmount = $subtotal;
        $this->totalAmount = $total;
    }

    public function getFormattedSubtotalProperty(): string
    {
        return \App\Helpers\CurrencyHelper::formatMoney($this->subtotalAmount, $this->currency);
    }

    public function getFormattedTotalProperty(): string
    {
        return \App\Helpers\CurrencyHelper::formatMoney($this->totalAmount, $this->currency);
    }

    /**
     * Get formatted price for individual cart item
     */
    public function getFormattedItemPrice($item): string
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $itemPrice = $item->price;

        // Convert item price to display currency if different
        if ($itemCurrency !== $this->currency) {
            try {
                $itemPrice = \App\Helpers\CurrencyHelper::convert(
                    $item->price,
                    $itemCurrency,
                    $this->currency
                );
            } catch (Exception) {
                // Fallback to original price if conversion fails
                $itemPrice = $item->price;
            }
        }

        return \App\Helpers\CurrencyHelper::formatMoney($itemPrice, $this->currency);
    }

    /**
     * Get formatted total price for individual cart item (price * quantity)
     */
    public function getFormattedItemTotal($item): string
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $itemPrice = $item->price;

        // Convert item price to display currency if different
        if ($itemCurrency !== $this->currency) {
            try {
                $itemPrice = \App\Helpers\CurrencyHelper::convert(
                    $item->price,
                    $itemCurrency,
                    $this->currency
                );
            } catch (Exception) {
                // Fallback to original price if conversion fails
                $itemPrice = $item->price;
            }
        }

        $total = $itemPrice * $item->quantity;

        return \App\Helpers\CurrencyHelper::formatMoney($total, $this->currency);
    }

    public function updateQuantity($id, $quantity): void
    {
        try {
            if ($quantity > 0 && $quantity <= 10) {
                // Get current item to preserve its attributes
                $currentItem = Cart::get($id);

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
            $numericPrice = (float) preg_replace('/[^\d.]/', '', $price);
            $itemCurrency = $currency ?? $this->currency;

            Cart::add([
                'id' => $domain,
                'name' => $domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'currency' => $itemCurrency,
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->updateCartTotal();
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

    public function removeItem($id): void
    {
        try {
            Cart::remove($id);
            $this->updateCartTotal();
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

    /**
     * Prepare cart data for payment processing
     */
    public function prepareCartForPayment(): array
    {
        $cartItems = [];

        foreach ($this->items as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $item->price;

            // Convert item price to display currency if different
            if ($itemCurrency !== $this->currency) {
                try {
                    $itemPrice = \App\Helpers\CurrencyHelper::convert(
                        $item->price,
                        $itemCurrency,
                        $this->currency
                    );
                } catch (Exception) {
                    // Fallback to original price if conversion fails
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

        return $cartItems;
    }

    /**
     * Store cart data in session for payment processing
     */
    public function proceedToPayment(): void
    {
        if ($this->items->isEmpty()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Your cart is empty',
            ]);

            return;
        }

        // Store cart data in session for payment processing
        session(['cart' => $this->prepareCartForPayment()]);

        // Redirect to payment page using Livewire's redirect method
        $this->redirect(route('payment.index'));
    }

    public function render(): View
    {
        return view('livewire.cart-component');
    }
}
