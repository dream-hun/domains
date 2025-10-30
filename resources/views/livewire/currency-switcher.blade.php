<x-dropdown align="right" width="140px">
    <x-slot name="trigger">
        <span class="d-inline-flex align-items-center gap-2 cursor-pointer" data-test="currency-selector">
            <span class="fw-medium">{{ $selectedCurrency }}</span>
            <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
        </span>
    </x-slot>

    <x-slot name="content">
        @foreach ($availableCurrencies as $code => $currency)
            <button type="button" wire:click="selectCurrency('{{ $code }}')" x-on:click="open = false"
                data-test="currency-{{ $code }}"
                class="dropdown-item text-start py-2 px-3 {{ $selectedCurrency === $code ? 'fw-bold' : '' }}"
                style="transition: all 0.2s ease; background-color: transparent !important;">
                <span>{{ $code }}</span>
            </button>
        @endforeach
    </x-slot>
</x-dropdown>
