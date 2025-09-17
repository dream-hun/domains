<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CurrencySelector extends Component
{
    public $selectedCurrency;

    public $availableCurrencies = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'RWF' => 'Rwandan Franc',
        'KES' => 'Kenyan Shilling',
        'UGX' => 'Ugandan Shilling',
        'TZS' => 'Tanzanian Shilling',
    ];

    public function mount(): void
    {
        $this->selectedCurrency = CurrencyHelper::getUserCurrency();
    }

    public function updatedSelectedCurrency(): void
    {
        // Dispatch event to update all components
        $this->dispatch('currencyChanged', $this->selectedCurrency);

        // Store in session for persistence
        session(['selected_currency' => $this->selectedCurrency]);
    }

    public function render(): View
    {
        return view('livewire.currency-selector');
    }
}
