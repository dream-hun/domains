<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\DomainType;
use App\Helpers\CurrencyHelper;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class DomainSearch extends Component
{
    public $domain = '';

    public $extension = '';

    public $results = [];

    public $error = '';

    public $isSearching = false;

    public $cartTotal = 0;

    public $quantity = 1;

    public string $currentCurrency = 'RWF';

    protected $listeners = ['refreshCart' => '$refresh', 'currencyChanged' => 'handleCurrencyChanged'];

    private EppDomainService $eppService;

    private InternationalDomainService $internationalService;

    public function boot(EppDomainService $eppService, InternationalDomainService $internationalService): void
    {
        $this->eppService = $eppService;
        $this->internationalService = $internationalService;
    }

    public function mount(): void
    {
        // Set default extension to first TLD
        $firstTld = Cache::remember('active_tld', 3600, function () {
            return DomainPrice::where('status', 'active')->first();
        });

        if ($firstTld) {
            $this->extension = $firstTld->tld;
        }

        // Get current currency from session
        $this->currentCurrency = session('selected_currency', 'USD');
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->currentCurrency = $currency;
        // Force refresh of the component to show new prices
        $this->dispatch('$refresh');
    }

    public function render(): View
    {
        $tlds = Cache::remember('active_tlds', 3600, function () {
            return DomainPrice::where('status', 'active')->get();
        });

        return view('livewire.domain-search', [
            'tlds' => $tlds,
        ]);
    }

    public function search(): void
    {
        $this->validate([
            'domain' => [
                'required',
                'min:2',
                'regex:/^[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
            ],
            'extension' => 'required',
        ], [
            'domain.regex' => 'Domain name can only contain letters, numbers, and hyphens, and cannot start or end with a hyphen.',
            'extension.required' => 'Please select a domain extension.',
        ]);

        $this->resetExcept('domain', 'extension');
        $this->isSearching = true;

        try {
            // Get cached TLDs
            $tlds = Cache::remember('active_tlds', 3600, function () {
                return DomainPrice::where('status', 'active')->get();
            });

            if ($tlds->isEmpty()) {
                $this->error = 'No TLDs configured in the system.';

                return;
            }

            // Get the selected primary TLD
            $primaryTld = $tlds->where('tld', $this->extension)->first();
            if (! $primaryTld) {
                $this->error = 'Selected extension is not available.';

                return;
            }

            $results = [];
            $cartContent = Cart::getContent();

            // Check primary domain
            $primaryDomainName = mb_strtolower($this->domain.'.'.mb_ltrim($primaryTld->tld, '.'));
            $this->checkAndAddDomain($results, $primaryDomainName, $primaryTld, $cartContent, true);

            // Check all other TLDs
            foreach ($tlds->where('tld', '!=', $primaryTld->tld) as $tld) {
                $domainWithTld = mb_strtolower($this->domain.'.'.mb_ltrim($tld->tld, '.'));
                $this->checkAndAddDomain($results, $domainWithTld, $tld, $cartContent, false);
            }

            $this->results = $results;
            Log::debug('Final search results:', ['count' => count($results)]);

        } catch (Exception $e) {
            Log::error('Domain check error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = 'An error occurred while searching for domains.';
        } finally {
            $this->isSearching = false;

            $this->dispatch('searchComplete');
        }
    }

    public function addToCart($domain, $price): void
    {
        try {
            $cartContent = Cart::getContent();
            if ($cartContent->firstWhere('id', $domain)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Domain is already in your cart.',
                ]);

                return;
            }

            Log::debug('Adding to cart:', [
                'domain' => $domain,
                'price' => $price,
            ]);

            Cart::add([
                'id' => $domain,
                'name' => $domain,
                'price' => $price,
                'quantity' => 1,
                'attributes' => [
                    'domain' => $domain,
                    'user_id' => auth()->id(),
                    'added_at' => now()->timestamp,
                ],
                'associatedModel' => Domain::class,
            ]);
            $this->dispatch('refreshCart');
            Log::debug('Cart after addition:', [
                'total' => Cart::getTotal(),
                'items' => Cart::getContent()->toArray(),
            ]);
            if (isset($this->results[$domain])) {
                $this->results[$domain]['in_cart'] = true;
            }
            $this->dispatch('update-cart')->to(CartTotal::class);

        } catch (Exception $e) {
            Log::error('Add to cart error:', [
                'domain' => $domain,
                'price' => $price,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add domain to cart. Please try again.',
            ]);
        }
    }

    public function removeFromCart(string $domain): void
    {
        try {
            // Remove from cart
            Cart::remove($domain);

            // Dispatch cart update event
            $this->dispatch('update-cart')->to(CartTotal::class);
            $this->dispatch('refreshCart');

            // Update the in_cart status for this domain in results
            if (isset($this->results[$domain])) {
                $this->results[$domain]['in_cart'] = false;
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $domain.' removed from cart.',
            ]);

        } catch (Exception $e) {
            Log::error('Remove from cart error:', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to remove domain from cart. Please try again.',
            ]);
        }
    }

    /**
     * Check a domain and add it to results if check succeeds
     */
    private function checkAndAddDomain(array &$results, string $domainName, $tld, $cartContent, bool $isPrimary): void
    {
        try {
            // Check if the domain is international
            $isInternational = isset($tld->type) && $tld->type === DomainType::International;

            if ($isInternational) {
                // Use international domain service for international domains
                Log::debug('Checking international domain:', ['domain' => $domainName]);
                $checkResult = $this->internationalService->checkAvailability([$domainName]);

                // Always process the result for international domains
                $rawPrice = (int) ($tld->register_price);

                // Convert price to current currency if needed
                $convertedPrice = $rawPrice;
                try {
                    if ($this->currentCurrency !== 'USD') {
                        $convertedPrice = CurrencyHelper::convertFromUSD($rawPrice / 1000, $this->currentCurrency);
                    }
                } catch (Exception $e) {
                    Log::warning('Currency conversion failed', ['error' => $e->getMessage()]);
                }

                $results[$domainName] = [
                    'available' => $checkResult['available'] ?? false,
                    'reason' => $checkResult['reason'] ?? 'Unknown status',
                    'register_price' => $convertedPrice,
                    'transfer_price' => $tld->transfer_price,
                    'renewal_price' => $tld->renewal_price,
                    'formatted_price' => CurrencyHelper::formatMoney($convertedPrice, $this->currentCurrency),
                    'in_cart' => $cartContent->has($domainName),
                    'is_primary' => $isPrimary,
                    'is_international' => true,
                ];

                Log::debug('International domain check processed', [
                    'domain' => $domainName,
                    'available' => $checkResult['available'] ?? false,
                    'raw_price' => $rawPrice,
                ]);
            } else {
                // Use EPP service for local domains
                $eppResults = $this->eppService->checkDomain([$domainName]);

                if ($eppResults !== [] && isset($eppResults[$domainName])) {
                    $result = $eppResults[$domainName];
                    $rawPrice = (int) ($tld->register_price);

                    // Convert price to current currency if needed
                    $convertedPrice = $rawPrice;
                    try {
                        if ($this->currentCurrency !== 'RWF') {
                            $convertedPrice = CurrencyHelper::convert($rawPrice, 'RWF', $this->currentCurrency);
                        }
                    } catch (Exception $e) {
                        Log::warning('Currency conversion failed', ['error' => $e->getMessage()]);
                    }

                    $results[$domainName] = [
                        'available' => $result->available,
                        'reason' => $result->reason,
                        'register_price' => $convertedPrice,
                        'transfer_price' => $tld->transfer_price,
                        'renewal_price' => $tld->renewal_price,
                        'formatted_price' => CurrencyHelper::formatMoney($convertedPrice, $this->currentCurrency),
                        'in_cart' => $cartContent->has($domainName),
                        'is_primary' => $isPrimary,
                        'is_international' => false,
                    ];

                    Log::debug('Local domain check successful', [
                        'domain' => $domainName,
                        'available' => $result->available,
                        'raw_price' => $rawPrice,
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Domain check error:', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
