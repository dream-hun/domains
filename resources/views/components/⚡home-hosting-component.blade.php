<?php

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    /** @var Collection<int, \App\Models\HostingPlan> */
    public $hostingPlans;

    public string $selectedCurrency = '';

    protected $listeners = [
        'currencyChanged' => 'handleCurrencyChanged',
        'currency-changed' => 'handleCurrencyChanged',
    ];

    public function mount(?Collection $hostingPlans = null, ?string $selectedCurrency = null): void
    {
        $this->hostingPlans = $hostingPlans ?? collect();
        $this->selectedCurrency = $selectedCurrency ?? CurrencyHelper::getUserCurrency();
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->selectedCurrency = $currency;
    }
};
?>

<div>
<section class="rts-plan section__padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="rts-section text-center w-560">
                    <h3 class="rts-section__title" data-sal="slide-down" data-sal-delay="300"
                        data-sal-duration="800">
                        Choose Your Web Hosting Plan</h3>
                    <p class="rts-section__description" data-sal="slide-down" data-sal-delay="400"
                        data-sal-duration="800">Shared hosting is the easiest, most economical way to get your website
                        connected to the Internet so you can start building it.
                    </p>
                </div>
            </div>
            <!-- PLAN -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="rts-plan__table">
                        @php
                            $displayPlans = $hostingPlans->take(4);
                            $allFeatures = $displayPlans
                                ->flatMap(fn($plan) => $plan->planFeatures)
                                ->pluck('hostingFeature')
                                ->filter()
                                ->unique('id')
                                ->values();
                        @endphp
                        <table class="table-bordered">
                            <!-- thead -->
                            <thead>
                                <tr>
                                    <th class="package__left">
                                        <img src="assets/images/pricing/pricing-image.svg" alt="">
                                    </th>
                                    @foreach ($displayPlans as $index => $plan)
                                        @php
                                            $pricesForCurrency = $plan->planPrices->filter(
                                                fn ($p) => $p->currency?->code === $selectedCurrency
                                            );
                                            $lowestPlanPrice = $pricesForCurrency->sortBy('regular_price')->first();
                                        @endphp
                                        <th class="package__item">
                                            <div class="package__item__info">
                                                <span class="package__type">{{ $plan->name }}</span>
                                                @if ($lowestPlanPrice)
                                                    <span class="start">Starting at
                                                        {{ $lowestPlanPrice->getFormattedPrice('regular_price', $selectedCurrency) }}/mo*</span>
                                                @else
                                                    <span class="start">Contact for pricing</span>
                                                @endif
                                                <form action="#">
                                                    <select name="select" id="select{{ $index }}"
                                                        class="price__select">
                                                        @forelse ($pricesForCurrency as $price)
                                                            <option value="{{ $price->id }}">
                                                                {{ $price->getFormattedPrice('regular_price', $selectedCurrency) }}/mo
                                                            </option>
                                                        @empty
                                                            <option disabled>Contact for pricing</option>
                                                        @endforelse
                                                    </select>
                                                    <button type="submit" aria-label="buy package"
                                                        class="primary__btn primary__bg buy__now">Buy Now</button>
                                                </form>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <!-- tbody -->
                            <tbody>
                                @foreach ($allFeatures as $feature)
                                    <tr data-filter="hardware" class="">
                                        <td class="package__left">{{ $feature->name }}</td>
                                        @foreach ($displayPlans as $plan)
                                            @php
                                                $planFeature = $plan->planFeatures->firstWhere(
                                                    'hosting_feature_id',
                                                    $feature->id,
                                                );
                                            @endphp
                                            <td class="package__item">
                                                @if ($planFeature)
                                                    @if ($planFeature->is_unlimited)
                                                        Unlimited
                                                    @elseif($planFeature->is_included)
                                                        <i class="fa-regular fa-check"></i>
                                                    @elseif($planFeature->feature_value === 'true')
                                                        <i class="fa-regular fa-check"></i>
                                                    @elseif($planFeature->feature_value === 'false')
                                                        <i class="fa-regular fa-xmark"></i>
                                                    @elseif($planFeature->custom_text)
                                                        {{ $planFeature->custom_text }}
                                                    @else
                                                        {{ $planFeature->feature_value ?? '-' }}
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>