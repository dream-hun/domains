@props(['domainPrice', 'priceType' => 'register_price'])

<div class="domain-price-display">
    @php
        $currency = \App\Helpers\CurrencyHelper::getUserCurrency();
        $formattedPrice = $domainPrice->getFormattedPrice($priceType, $currency);
    @endphp

    <span class="price">{{ $formattedPrice }}</span>

    @if ($currency !== 'USD')
        @php
            $usdPrice = $domainPrice->getFormattedPrice($priceType, 'USD');
        @endphp
        <small class="text-muted">({{ $usdPrice }} USD)</small>
    @endif
</div>
