<?php

declare(strict_types=1);

namespace App\Livewire;

use Cknow\Money\Money;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CartComponent extends Component
{
    public $items;

    public $subtotalAmount = 0;

    public $totalAmount = 0;

    protected $listeners = ['refreshCart' => '$refresh'];

    public function mount(): void
    {
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

        $this->subtotalAmount = Cart::getSubTotal();
        $this->totalAmount = Cart::getTotal();
    }

    public function getFormattedSubtotalProperty(): string
    {
        return Money::RWF($this->subtotalAmount)->format();
    }

    public function getFormattedTotalProperty(): string
    {
        return Money::RWF($this->totalAmount)->format();
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

    public function addToCart($domain, $price): void
    {
        try {
            // Convert price string to numeric value (remove currency symbols)
            $numericPrice = (float) preg_replace('/[^\d.]/', '', $price);

            Cart::add([
                'id' => $domain,
                'name' => $domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
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

    public function render(): View
    {
        return view('livewire.cart-component');
    }
}
