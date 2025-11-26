<?php

declare(strict_types=1);

namespace App\Livewire\Hosting;

use App\Enums\DomainType;
use App\Helpers\CurrencyHelper;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.user')]
class Configuration extends Component
{
    public HostingPlan $plan;

    public string $billingCycle = 'monthly';

    public int $currentStep = 1;

    // Step 2: Domain connection
    public string $domainOption = 'new'; // 'new' or 'existing'

    public string $newDomainSource = 'new_purchase'; // 'already_in_cart' or 'new_purchase'

    public string $domainSearchQuery = '';

    public array $domainSearchResults = [];

    public bool $isSearching = false;

    public ?string $selectedDomain = null;

    public ?float $selectedDomainPrice = null;

    public bool $domainConfirmed = false;

    // For existing domains
    public string $existingDomainSource = 'owned'; // 'owned' or 'external'

    public string $externalDomainName = '';

    public ?int $selectedOwnedDomainId = null;

    private EppDomainService $eppService;

    private InternationalDomainService $internationalService;

    public function boot(EppDomainService $eppService, InternationalDomainService $internationalService): void
    {
        $this->eppService = $eppService;
        $this->internationalService = $internationalService;
    }

    public function mount(HostingPlan $plan): void
    {
        $this->plan = $plan->load(['category', 'planPrices', 'planFeatures.hostingFeature']);

        $this->billingCycle = request()->query('billing_cycle', 'monthly');

        if (! in_array($this->billingCycle, ['monthly', 'annually'], true)) {
            $this->billingCycle = 'monthly';
        }
    }

    public function updatedDomainOption(): void
    {
        $this->resetErrorBag();
        $this->selectedDomain = null;
        $this->selectedDomainPrice = null;
        $this->domainSearchResults = [];
        $this->domainSearchQuery = '';
        $this->externalDomainName = '';
        $this->selectedOwnedDomainId = null;
        $this->domainConfirmed = false;
    }

    public function updatedNewDomainSource(): void
    {
        $this->resetErrorBag();
        $this->selectedDomain = null;
        $this->selectedDomainPrice = null;
        $this->domainSearchResults = [];
        $this->domainSearchQuery = '';
        $this->domainConfirmed = false;
    }

    public function updatedExistingDomainSource(): void
    {
        $this->resetErrorBag();
        $this->selectedDomain = null;
        $this->externalDomainName = '';
        $this->selectedOwnedDomainId = null;
        $this->domainConfirmed = false;
    }

    public function updatedBillingCycle(): void
    {
        $this->resetErrorBag();
    }

    public function updatedExternalDomainName(): void
    {
        $this->domainConfirmed = false;
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= 2) {
            $this->currentStep = $step;
        }
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 2) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    #[Computed]
    public function selectedPrice(): ?HostingPlanPrice
    {
        return $this->plan->planPrices->firstWhere('billing_cycle', $this->billingCycle);
    }

    #[Computed]
    public function formattedPrice(): string
    {
        $price = $this->selectedPrice;

        if (! $price) {
            return 'N/A';
        }

        return $price->getFormattedPrice('regular_price');
    }

    #[Computed]
    public function billingPeriodLabel(): string
    {
        return $this->billingCycle === 'monthly' ? '/mo' : '/yr';
    }

    #[Computed]
    public function highlightedFeatures(): array
    {
        return $this->plan->planFeatures
            ->where('is_included', true)
            ->take(4)
            ->map(function ($planFeature) {
                $feature = $planFeature->hostingFeature;

                return [
                    'name' => $feature?->name ?? 'Feature',
                    'value' => $planFeature->getDisplayText(),
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function totalPrice(): float
    {
        $price = $this->selectedPrice;

        if (! $price) {
            return 0;
        }

        $userCurrency = CurrencyHelper::getUserCurrency();
        $hostingPrice = $price->getPriceInCurrency('regular_price', $userCurrency);

        // Add domain price if a new domain is selected
        if ($this->domainOption === 'new' && $this->selectedDomainPrice) {
            return $hostingPrice + $this->selectedDomainPrice;
        }

        return $hostingPrice;
    }

    #[Computed]
    public function formattedTotalPrice(): string
    {
        try {
            $userCurrency = CurrencyHelper::getUserCurrency();

            return CurrencyHelper::formatMoney($this->totalPrice, $userCurrency);
        } catch (Exception) {
            return $this->formattedPrice;
        }
    }

    #[Computed]
    public function userOwnedDomains(): Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        return Domain::query()
            ->where('owner_id', Auth::id())
            ->whereIn('status', [\App\Enums\DomainStatus::Active])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function domainsInCart(): Collection
    {
        $cartContent = Cart::getContent();

        return $cartContent->filter(function ($item) {
            return $item->attributes->get('type') === 'domain';
        });
    }

    #[Computed]
    public function finalDomainName(): ?string
    {
        if ($this->domainOption === 'new') {
            return $this->selectedDomain;
        }

        // Existing domain
        if ($this->existingDomainSource === 'owned') {
            return $this->selectedDomain;
        }

        // External domain
        return $this->externalDomainName ?: null;
    }

    #[Computed]
    public function canAddToCart(): bool
    {
        return $this->domainConfirmed && $this->finalDomainName !== null;
    }

    public function searchDomains(): void
    {
        $this->validate([
            'domainSearchQuery' => ['required', 'min:2'],
        ], [
            'domainSearchQuery.required' => 'Please enter a domain name to search.',
            'domainSearchQuery.min' => 'Domain name must be at least 2 characters.',
        ]);

        $this->isSearching = true;
        $this->domainSearchResults = [];
        $this->domainConfirmed = false;

        try {
            $tlds = Cache::remember('active_tlds', 3600, fn () => DomainPrice::query()->where('status', 'active')->get());

            if ($tlds->isEmpty()) {
                $this->addError('domainSearchQuery', 'No TLDs configured in the system.');

                return;
            }

            $searchTerm = mb_strtolower(mb_trim($this->domainSearchQuery));

            // Remove any existing extension from the search term
            $searchTerm = preg_replace('/\.[a-z]{2,}$/i', '', $searchTerm);

            $results = [];
            $cartContent = Cart::getContent();
            $currentCurrency = CurrencyHelper::getUserCurrency();

            foreach ($tlds->take(10) as $tld) {
                $domainName = $searchTerm.'.'.mb_ltrim($tld->tld, '.');

                try {
                    $isInternational = isset($tld->type) && $tld->type === DomainType::International;

                    if ($isInternational) {
                        $checkResult = $this->internationalService->checkAvailability([$domainName]);
                        $available = $checkResult['available'] ?? false;
                    } else {
                        $eppResults = $this->eppService->checkDomain([$domainName]);
                        $available = isset($eppResults[$domainName]) && $eppResults[$domainName]->available;
                    }

                    // Convert price from cents to dollars, then to user currency
                    $rawPriceInCents = (int) $tld->register_price;
                    $priceInUSD = $rawPriceInCents / 100;
                    $convertedPrice = $priceInUSD;

                    $renewalPriceInCents = (int) $tld->renewal_price;
                    $renewalInUSD = $renewalPriceInCents / 100;
                    $convertedRenewal = $renewalInUSD;

                    if ($currentCurrency !== 'USD') {
                        $convertedPrice = CurrencyHelper::convertFromUSD($priceInUSD, $currentCurrency);
                        $convertedRenewal = CurrencyHelper::convertFromUSD($renewalInUSD, $currentCurrency);
                    }

                    $results[$domainName] = [
                        'domain' => $domainName,
                        'available' => $available,
                        'price' => $convertedPrice,
                        'renewal_price' => $convertedRenewal,
                        'formatted_price' => CurrencyHelper::formatMoney($convertedPrice, $currentCurrency),
                        'formatted_renewal' => CurrencyHelper::formatMoney($convertedRenewal, $currentCurrency),
                        'in_cart' => $cartContent->has($domainName),
                        'tld' => $tld->tld,
                    ];
                } catch (Exception $e) {
                    Log::warning('Domain check failed', ['domain' => $domainName, 'error' => $e->getMessage()]);
                }
            }

            $this->domainSearchResults = $results;

        } catch (Exception $e) {
            Log::error('Domain search error', ['error' => $e->getMessage()]);
            $this->addError('domainSearchQuery', 'An error occurred while searching for domains.');
        } finally {
            $this->isSearching = false;
        }
    }

    public function selectDomain(string $domain, float $price): void
    {
        $this->selectedDomain = $domain;
        $this->selectedDomainPrice = $price;
        $this->domainConfirmed = false;
    }

    public function selectCartDomain(string $domain): void
    {
        $this->selectedDomain = $domain;
        $this->selectedDomainPrice = null; // Already in cart, no additional price
        $this->domainConfirmed = false;
    }

    public function selectOwnedDomain(int $domainId): void
    {
        $domain = Domain::find($domainId);

        if ($domain && $domain->owner_id === Auth::id()) {
            $this->selectedOwnedDomainId = $domainId;
            $this->selectedDomain = $domain->name;
            $this->domainConfirmed = false;
        }
    }

    /**
     * Confirm the domain selection (Connect To Hosting button)
     * This just validates and confirms the selection, does NOT add to cart
     */
    public function confirmDomainSelection(): void
    {
        $domainName = $this->finalDomainName;

        if (! $domainName) {
            $this->addError('base', 'Please select or enter a domain name.');

            return;
        }

        // Validate domain format
        if (! preg_match('/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i', $domainName)) {
            $this->addError('base', 'Please enter a valid domain name.');

            return;
        }

        // Check external domain restriction
        if ($this->domainOption === 'existing' && $this->existingDomainSource === 'external') {
            if (! $this->plan->category->allowsExternalDomain()) {
                $this->addError('base', 'This plan requires a domain registered with us. External domains cannot be used.');

                return;
            }
        }

        $this->resetErrorBag();
        $this->domainConfirmed = true;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Domain '{$domainName}' connected. Click 'Add to Cart' to proceed.",
        ]);
    }

    /**
     * Add hosting (and optionally domain) to cart
     * This is called from the sticky bottom bar
     */
    public function addToCart(): void
    {
        $domainName = $this->finalDomainName;

        if (! $domainName) {
            $this->addError('base', 'Please select or enter a domain name first.');

            return;
        }

        if (! $this->domainConfirmed) {
            $this->addError('base', 'Please click "Connect To Hosting" to confirm your domain selection first.');

            return;
        }

        // Check if hosting is already in cart for this domain
        $cartContent = Cart::getContent();
        $alreadyInCart = $cartContent->first(function ($item) use ($domainName): bool {
            $isHosting = $item->attributes->get('type') === 'hosting';

            if (! $isHosting) {
                return false;
            }

            return mb_strtolower((string) $item->attributes->get('linked_domain')) === mb_strtolower($domainName);
        });

        if ($alreadyInCart) {
            $this->addError('base', 'A hosting plan is already in your cart for this domain.');

            return;
        }

        try {
            /** @var HostingPlanPrice|null $priceModel */
            $priceModel = $this->selectedPrice;

            if (! $priceModel) {
                $this->addError('base', 'Price not available for this billing cycle.');

                return;
            }

            $userCurrency = CurrencyHelper::getUserCurrency();
            $hostingPrice = $priceModel->getPriceInCurrency('regular_price', $userCurrency);

            // Add Hosting Plan to Cart
            $cartId = "hosting-{$this->plan->id}-{$this->billingCycle}-{$domainName}";

            Cart::add([
                'id' => $cartId,
                'name' => "{$this->plan->name} ({$this->billingCycle})",
                'price' => $hostingPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'hosting',
                    'hosting_plan_id' => $this->plan->id,
                    'hosting_plan_price_id' => $priceModel->id,
                    'billing_cycle' => $this->billingCycle,
                    'linked_domain' => $domainName,
                    'domain_name' => $domainName,
                    'is_existing_domain' => $this->domainOption === 'existing',
                    'currency' => $userCurrency,
                    'metadata' => [
                        'hosting_plan_id' => $this->plan->id,
                        'hosting_plan_price_id' => $priceModel->id,
                        'billing_cycle' => $this->billingCycle,
                        'linked_domain' => $domainName,
                        'plan' => $this->plan->only(['id', 'name', 'slug']),
                        'price' => [
                            'id' => $priceModel->id,
                            'billing_cycle' => $this->billingCycle,
                            'currency' => $userCurrency,
                        ],
                    ],
                    'added_at' => now()->timestamp,
                ],
            ]);

            // If it's a new domain purchase, add the domain to cart as well
            if ($this->domainOption === 'new' && $this->newDomainSource === 'new_purchase' && $this->selectedDomainPrice) {
                $domainCartId = $domainName;

                // Only add if not already in cart
                if (! Cart::get($domainCartId)) {
                    Cart::add([
                        'id' => $domainCartId,
                        'name' => $domainName,
                        'price' => $this->selectedDomainPrice,
                        'quantity' => 1,
                        'attributes' => [
                            'type' => 'domain',
                            'domain' => $domainName,
                            'domain_name' => $domainName,
                            'currency' => $userCurrency,
                            'user_id' => Auth::id(),
                            'added_at' => now()->timestamp,
                        ],
                    ]);
                }
            }

            $this->dispatch('refreshCart');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Hosting plan added to cart.',
            ]);

            $this->redirect(route('cart.index'));

        } catch (Exception $e) {
            Log::error('Failed to add hosting to cart', ['error' => $e->getMessage()]);
            $this->addError('base', 'Failed to add to cart: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.hosting.configuration');
    }
}
