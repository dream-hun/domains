<div class="currency-selector">
    <select wire:model.live="selectedCurrency" class="form-select form-select-sm">
        @foreach ($availableCurrencies as $code => $name)
            <option value="{{ $code }}">
                {{ \App\Helpers\CurrencyHelper::getCurrencySymbol($code) }} {{ $code }} - {{ $name }}
            </option>
        @endforeach
    </select>
</div>
