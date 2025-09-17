<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Livewire\Component;

final class CartSummary extends Component
{
    public $currency;

    protected $listeners = ['refreshCart' => '$refresh', 'currencyChanged' => 'updateCurrency'];

    public function mount(): void
    {
        $this->currency = CurrencyHelper::getUserCurrency();
    }

    public function updateCurrency($newCurrency): void
    {
        $this->currency = $newCurrency;
    }

    /**
     * @throws Exception
     */
    public function getFormattedTotalProperty(): string
    {
        $cartItems = Cart::getContent();
        $total = 0;

        foreach ($cartItems as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            if ($itemCurrency !== $this->currency) {
                try {
                    $convertedPrice = CurrencyHelper::convert(
                        $item->price,
                        $itemCurrency,
                        $this->currency
                    );
                    $total += $convertedPrice * $item->quantity;
                } catch (Exception) {

                    $total += $item->price * $item->quantity;
                }
            } else {
                $total += $item->price * $item->quantity;
            }
        }

        return CurrencyHelper::formatMoney($total, $this->currency);
    }

    public function getCartItemsCountProperty(): int
    {
        return Cart::getContent()->count();
    }

    public function render(): Factory|Application|View|\Illuminate\View\View
    {
        return view('livewire.cart-summary');
    }
}
