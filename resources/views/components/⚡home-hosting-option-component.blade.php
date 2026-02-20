<?php

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    /** @var Collection<int, \App\Models\HostingCategory> */
    public $hostingCategories;

    public string $selectedCurrency = '';

    protected $listeners = [
        'currencyChanged' => 'handleCurrencyChanged',
        'currency-changed' => 'handleCurrencyChanged',
    ];

    public function mount(?Collection $hostingCategories = null, ?string $selectedCurrency = null): void
    {
        $this->hostingCategories = $hostingCategories ?? collect();
        $this->selectedCurrency = $selectedCurrency ?? CurrencyHelper::getUserCurrency();
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->selectedCurrency = $currency;
    }
};
?>

<div>
<div class="rts-hosting-type">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="rts-hosting-type__section">
                        <h3 class="title" data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">
                            Multiple
                            Hosting Options</h3>
                        <p data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">No matter your hosting
                            requirements, our platform will fit your needs.</p>
                        <div class="rts-slider__btn hosting-slider">
                            <div class="slide__btn rts-prev"><i class="fa-light fa-arrow-left"></i></div>
                            <div class="slide__btn rts-next"><i class="fa-light fa-arrow-right"></i></div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- hosting option -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="rts-hosting-type__slider">
                        <div class="swiper-wrapper">
                            @foreach ($hostingCategories as $category)
                                <div class="swiper-slide">
                                    <div class="rts-hosting-type__single">
                                        @if ($category->slug == 'shared-hosting')
                                            <div class="hosting-icon">
                                                <img src="assets/images/hosting/04.svg" alt="">
                                            </div>
                                        @elseif ($category->slug == 'managed-wordpress-hosting')
                                            <div class="hosting-icon">
                                                <img src="assets/images/hosting/05.svg" alt="">
                                            </div>
                                        @elseif ($category->slug == 'dedicated-hosting')
                                            <div class="hosting-icon">
                                                <img src="assets/images/service/dedicated__hosting.svg"
                                                    alt="">
                                            </div>
                                        @elseif ($category->slug == 'vps-hosting')
                                            <div class="hosting-icon">
                                                <img src="assets/images/hosting/02.svg" alt="">
                                            </div>
                                        @elseif ($category->slug == 'reseller-hosting')
                                            <div class="hosting-icon">
                                                <img src="assets/images/service/resseller__hosting.svg"
                                                    alt="">
                                            </div>
                                        @elseif ($category->slug == 'cloud-hosting')
                                            <div class="hosting-icon">
                                                <img src="assets/images/service/cloud__hosting.svg" alt="">
                                            </div>
                                        @else
                                            <div class="hosting-icon">
                                                <img src="assets/images/hosting/01.svg" alt="">
                                            </div>
                                        @endif
                                        <a href="{{ url('/hosting/' . $category->slug) }}"
                                            class="title">{{ $category->name }}</a>
                                        <p class="excerpt">{{ Str::limit($category->description, 60) }}</p>
                                        @php
                                            $allPrices = $category->plans->flatMap(fn($p) => $p->planPrices);
                                            
                                            // First try to find prices in the selected currency
                                            $pricesForCurrency = $allPrices->filter(
                                                fn($p) => $p->currency?->code === $selectedCurrency
                                            );
                                            $lowestPriceModel = $pricesForCurrency->sortBy('regular_price')->first();
                                            
                                            // Fallback to lowest price in any currency if selected currency has no prices
                                            if (!$lowestPriceModel) {
                                                $lowestPriceModel = $allPrices->sortBy('regular_price')->first();
                                            }
                                        @endphp
                                        @if ($lowestPriceModel)
                                            <h6 class="price__start">Starting from
                                                {{ $lowestPriceModel->getFormattedPrice('regular_price', $selectedCurrency) }}/month</h6>
                                        @else
                                            <h6 class="price__start">Contact for pricing</h6>
                                        @endif
                                        <a href="{{ url('/hosting/' . $category->slug) }}"
                                            class="primary__btn border__btn">See Plan <i
                                                class="fa-regular fa-long-arrow-right"></i></a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>