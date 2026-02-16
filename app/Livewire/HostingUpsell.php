<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Enums\Hosting\HostingPlanStatus;
use App\Models\HostingPlan;
use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class HostingUpsell extends Component
{
    use HasCurrency;

    public array $plans = [];

    public array $cartDomains = [];

    public ?string $selectedDomain = null;

    public string $currencyCode = 'USD';

    protected $listeners = [
        'refreshCart' => 'hydrateCart',
        'currencyChanged' => 'handleCurrencyChanged',
    ];

    public function mount(): void
    {
        $this->currencyCode = $this->getUserCurrency()->code;
        $this->loadPlans();
        $this->hydrateCart();
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->currencyCode = mb_strtoupper($currency);
        $this->loadPlans();
    }

    public function hydrateCart(): void
    {
        $cartContent = Cart::getContent();
        $domains = [];

        foreach ($cartContent as $item) {
            $type = $item->attributes->get('type', 'domain');

            if ($type === 'hosting') {
                continue;
            }

            $domains[] = $item->attributes->get('domain_name')
                ?? $item->attributes->get('domain')
                ?? $item->name;
        }

        $this->cartDomains = array_values(array_unique(array_filter($domains)));

        if ($this->selectedDomain === null && $this->cartDomains !== []) {
            $this->selectedDomain = $this->cartDomains[0];
        } elseif ($this->selectedDomain && ! in_array($this->selectedDomain, $this->cartDomains, true)) {
            $this->selectedDomain = $this->cartDomains[0] ?? null;
        }
    }

    public function isHostingInCart(int $planId): bool
    {
        if (! $this->selectedDomain) {
            return false;
        }

        $cartContent = Cart::getContent();

        return $cartContent->contains(fn ($item): bool => $item->attributes->get('type') === 'hosting'
            && $item->attributes->get('hosting_plan_id') === $planId
            && $item->attributes->get('linked_domain') === $this->selectedDomain);
    }

    public function removeHosting(int $planId): void
    {
        $cartContent = Cart::getContent();

        $item = $cartContent->first(fn ($item): bool => $item->attributes->get('type') === 'hosting'
            && $item->attributes->get('hosting_plan_id') === $planId
            && $item->attributes->get('linked_domain') === $this->selectedDomain);

        if ($item) {
            Cart::remove($item->id);

            $this->dispatch('refreshCart');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Hosting removed from cart.',
            ]);
        }
    }

    public function addHosting(int $planId, string $billingCycle = 'monthly'): void
    {
        if ($this->cartDomains === []) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Add a domain to your cart before selecting hosting.',
            ]);

            return;
        }

        if (! $this->selectedDomain) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Select which domain should use this hosting plan.',
            ]);

            return;
        }

        try {
            /** @var HostingPlan|null $plan */
            $plan = HostingPlan::query()
                ->with(['planPrices' => function ($query): void {
                    $query->where('status', HostingPlanPriceStatus::Active)
                        ->where('is_current', true)
                        ->with('currency');
                }])
                ->where('status', HostingPlanStatus::Active)
                ->find($planId);

            if (! $plan) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Hosting plan not available.',
                ]);

                return;
            }

            $planPrice = $plan->planPrices
                ->firstWhere('billing_cycle', $billingCycle)
                ?? $plan->planPrices->first();

            if (! $planPrice) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Pricing information unavailable for this plan.',
                ]);

                return;
            }

            $cartContent = Cart::getContent();

            $alreadyInCart = $cartContent->first(function ($item): bool {
                $isHosting = $item->attributes->get('type') === 'hosting';

                if (! $isHosting) {
                    return false;
                }

                return $item->attributes->get('linked_domain') === $this->selectedDomain;
            });

            if ($alreadyInCart) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Hosting for this domain is already in your cart.',
                ]);

                return;
            }

            $price = $planPrice->getPriceInCurrency('regular_price');
            $durationMonths = $this->getBillingCycleMonths($planPrice->billing_cycle);

            Cart::add([
                'id' => sprintf('hosting-%d-%s', $planPrice->id, md5($this->selectedDomain)),
                'name' => sprintf('%s Hosting (%s)', $plan->name, ucfirst((string) $planPrice->billing_cycle)),
                'price' => $price,
                'quantity' => $durationMonths,
                'attributes' => [
                    'type' => 'hosting',
                    'currency' => $this->currencyCode,
                    'hosting_plan_id' => $plan->id,
                    'hosting_plan_pricing_id' => $planPrice->id,
                    'billing_cycle' => $planPrice->billing_cycle,
                    'linked_domain' => $this->selectedDomain,
                    'domain_name' => $this->selectedDomain,
                    'duration_months' => $durationMonths,
                    'metadata' => [
                        'hosting_plan_id' => $plan->id,
                        'hosting_plan_pricing_id' => $planPrice->id,
                        'billing_cycle' => $planPrice->billing_cycle,
                        'linked_domain' => $this->selectedDomain,
                        'duration_months' => $durationMonths,
                        'plan' => $plan->only(['id', 'name', 'slug']),
                        'price' => [
                            'id' => $planPrice->id,
                            'billing_cycle' => $planPrice->billing_cycle,
                            'currency' => $this->currencyCode,
                        ],
                    ],
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->dispatch('refreshCart');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Hosting added to cart successfully.',
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to add hosting to cart', [
                'plan_id' => $planId,
                'billing_cycle' => $billingCycle,
                'domain' => $this->selectedDomain,
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add hosting. Please try again.',
            ]);
        }
    }

    public function render(): Factory|View
    {
        return view('livewire.hosting-upsell', [
            'hasDomains' => $this->cartDomains !== [],
        ]);
    }

    private function loadPlans(): void
    {
        $plans = HostingPlan::query()
            ->with(['planPrices' => function ($query): void {
                $query->where('status', HostingPlanPriceStatus::Active)
                    ->where('is_current', true)
                    ->orderBy('billing_cycle')
                    ->with('currency');
            }])
            ->where('status', HostingPlanStatus::Active)
            ->orderByDesc('is_popular')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        $this->plans = $this->transformPlans($plans);
    }

    private function transformPlans(Collection $plans): array
    {
        return $plans->map(function (HostingPlan $plan): array {
            $prices = $plan->planPrices->map(function ($price): array {
                $converted = $price->getPriceInCurrency('regular_price');

                return [
                    'id' => $price->id,
                    'billing_cycle' => $price->billing_cycle,
                    'amount' => $converted,
                    'formatted' => $this->formatCurrency($converted, $this->currencyCode),
                ];
            })->keyBy('billing_cycle');

            $monthly = $prices->get('monthly') ?? $prices->first();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'tagline' => $plan->tagline,
                'description' => $plan->description,
                'is_popular' => $plan->is_popular,
                'monthly_price' => $monthly,
                'prices' => $prices->all(),
            ];
        })->all();
    }

    /**
     * Get billing cycle duration in months
     */
    private function getBillingCycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
            default => 1,
        };
    }
}
