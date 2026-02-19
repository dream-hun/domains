<?php

declare(strict_types=1);

namespace App\Livewire\Hosting;

use App\Helpers\CurrencyHelper;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

final class CategoryShow extends Component
{
    public HostingCategory $category;

    /** @var Collection<int, HostingPlan> */
    public Collection $plans;

    public string $selectedCurrency = '';

    /** @var array<string, string> */
    protected $listeners = [
        'currency-changed' => 'handleCurrencyChanged',
        'currencyChanged' => 'handleCurrencyChanged',
    ];

    /**
     * @param  Collection<int, HostingPlan>  $plans
     */
    public function mount(HostingCategory $category, Collection $plans): void
    {
        $this->category = $category;
        $this->plans = $plans;
        $this->selectedCurrency = CurrencyHelper::getUserCurrency();
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->selectedCurrency = mb_strtoupper($currency);
    }

    /**
     * Get the plan price for the given billing cycle in the selected currency,
     * with fallback to first available price when selected currency has no price.
     */
    public function getPriceForCycle(HostingPlan $plan, string $billingCycle): ?HostingPlanPrice
    {
        $prices = $plan->planPrices->where('billing_cycle', $billingCycle);

        $forCurrency = $prices->first(fn (HostingPlanPrice $p): bool => $p->currency?->code === $this->selectedCurrency);

        if ($forCurrency instanceof HostingPlanPrice) {
            return $forCurrency;
        }

        return $prices->sortBy('regular_price')->first();
    }

    /**
     * Get the cheapest monthly price across all plans in the selected currency,
     * with fallback to cheapest in any currency.
     */
    public function getCheapestMonthlyPrice(): ?HostingPlanPrice
    {
        $monthlyPrices = $this->plans
            ->flatMap(fn (HostingPlan $plan): Collection => $plan->planPrices->where('billing_cycle', 'monthly'))
            ->filter(fn (HostingPlanPrice $p): bool => $p->regular_price > 0);

        $forCurrency = $monthlyPrices
            ->filter(fn (HostingPlanPrice $p): bool => $p->currency?->code === $this->selectedCurrency)
            ->sortBy('regular_price')
            ->first();

        if ($forCurrency instanceof HostingPlanPrice) {
            return $forCurrency;
        }

        return $monthlyPrices->sortBy('regular_price')->first();
    }

    /**
     * Currency to use when formatting a price (selected currency if the price row matches, else the price's own currency).
     */
    public function displayCurrencyForPrice(HostingPlanPrice $price): string
    {
        return $price->currency?->code === $this->selectedCurrency
            ? $this->selectedCurrency
            : $price->getBaseCurrency();
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        return view('livewire.hosting.category-show');
    }
}
