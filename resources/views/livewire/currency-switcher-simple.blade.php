<div class="live-chat-has-dropdown">
    <x-dropdown>
        <x-slot name="trigger">
            <button type="button" class="btn btn-sm btn-white btn-outline-primary dropdown-toggle gap-2 text-white"
                data-test="currency-selector">
                <span>{{ $selectedCurrency }}</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </x-slot>

        <x-slot name="content">
            <div class="px-3 py-2 text-center fw-bold text-uppercase">
                Select Currency
            </div>
            @foreach ($availableCurrencies as $code => $currency)
                <button type="button" wire:click="selectCurrency('{{ $code }}')" x-on:click="open = false"
                    data-test="currency-{{ $code }}"
                    class="dropdown-item d-flex align-items-center justify-content-between text-center {{ $selectedCurrency === $code ? 'active' : '' }}">
                    <span class="d-flex align-items-center gap-2">
                        <span class="fs-5">{{ $currency['symbol'] }}</span>
                        <span class="fw-medium">{{ $code }}</span>
                    </span>
                    <span class="text-muted small">{{ $currency['name'] }}</span>
                </button>
            @endforeach
        </x-slot>
    </x-dropdown>
</div>
