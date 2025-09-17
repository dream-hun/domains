<div class="position-relative">
    <select wire:model.live="selectedCurrency">
        @foreach ($currencies as $currency)
            <option value="{{ $currency->code }}" @selected($currency->code === $currentCurrency->code)>
                {{ $currency->symbol }} {{ $currency->code }}
            </option>
        @endforeach
    </select>
</div>
