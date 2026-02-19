<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Domain\BuildDomainSearchResultAction;
use App\Models\Domain;
use App\Models\Tld;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

final class DomainSearch extends Component
{
    public string $domain = '';

    public string $extension = '';

    /** @var array<string, array<string, mixed>> */
    public array $results = [];

    public string $error = '';

    public string $currentCurrency = 'RWF';

    protected $listeners = ['refreshCart' => '$refresh'];

    private EppDomainService $eppService;

    private InternationalDomainService $internationalService;

    private BuildDomainSearchResultAction $buildDomainSearchResult;

    public function boot(
        EppDomainService $eppService,
        InternationalDomainService $internationalService,
        BuildDomainSearchResultAction $buildDomainSearchResult
    ): void {
        $this->eppService = $eppService;
        $this->internationalService = $internationalService;
        $this->buildDomainSearchResult = $buildDomainSearchResult;
    }

    public function mount(): void
    {
        $firstTld = $this->getActiveTlds()->first();

        if ($firstTld) {
            $this->extension = $firstTld->tld;
        }

        $this->currentCurrency = session('selected_currency', 'USD');
    }

    #[On('currencyChanged')]
    #[On('currency-changed')]
    public function handleCurrencyChanged(mixed $currency = null): void
    {
        $currency = mb_strtoupper((string) ($currency ?? ''));

        if ($currency === '') {
            return;
        }

        $this->currentCurrency = $currency;
        session(['selected_currency' => $this->currentCurrency]);

        if (empty($this->results)) {
            return;
        }

        $tlds = $this->getActiveTlds();
        $cartContent = Cart::getContent();

        foreach ($this->results as $domainName => $result) {
            $tldString = $result['tld'] ?? null;
            if ($tldString === null) {
                continue;
            }

            $tld = $tlds->firstWhere('tld', $tldString);
            if (! $tld instanceof Tld) {
                continue;
            }

            $this->results[$domainName] = $this->buildDomainSearchResult->handle(
                $tld,
                $domainName,
                $result['available'],
                $result['reason'],
                $this->currentCurrency,
                $cartContent->has($domainName),
                $result['is_primary'],
                $result['is_international']
            );
        }
    }

    public function render(): View
    {
        return view('livewire.domain-search', [
            'tlds' => $this->getActiveTlds(),
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

        try {
            $tlds = $this->getActiveTlds();

            if ($tlds->isEmpty()) {
                $this->error = 'No TLDs configured in the system.';

                return;
            }

            $primaryTld = $tlds->where('tld', $this->extension)->first();
            if (! $primaryTld) {
                $this->error = 'Selected extension is not available.';

                return;
            }

            $results = [];
            $cartContent = Cart::getContent();

            $primaryDomainName = mb_strtolower($this->domain.'.'.mb_ltrim($primaryTld->tld, '.'));
            $this->checkAndAddDomain($results, $primaryDomainName, $primaryTld, $cartContent, true);

            foreach ($tlds->where('tld', '!=', $primaryTld->tld) as $tld) {
                $domainWithTld = mb_strtolower($this->domain.'.'.mb_ltrim($tld->tld, '.'));
                $this->checkAndAddDomain($results, $domainWithTld, $tld, $cartContent, false);
            }

            $this->results = $results;
            Log::debug('Final search results:', ['count' => count($results)]);

        } catch (Exception $exception) {
            Log::error('Domain check error:', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->error = 'An error occurred while searching for domains.';
        } finally {
            $this->dispatch('searchComplete');
        }
    }

    public function addToCart($domain, $price, ?string $currency = null): void
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

            $itemCurrency = $currency !== null && $currency !== '' ? $currency : $this->currentCurrency;

            Log::debug('Adding to cart:', [
                'domain' => $domain,
                'price' => $price,
                'currency' => $itemCurrency,
            ]);

            Cart::add([
                'id' => $domain,
                'name' => $domain,
                'price' => $price,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'domain' => $domain,
                    'domain_name' => $domain,
                    'currency' => $itemCurrency,
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

        } catch (Exception $exception) {
            Log::error('Add to cart error:', [
                'domain' => $domain,
                'price' => $price,
                'error' => $exception->getMessage(),
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
            $this->removeLinkedHosting($domain);

            Cart::remove($domain);

            $this->dispatch('update-cart')->to(CartTotal::class);
            $this->dispatch('refreshCart');

            if (isset($this->results[$domain])) {
                $this->results[$domain]['in_cart'] = false;
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $domain.' removed from cart.',
            ]);

        } catch (Exception $exception) {
            Log::error('Remove from cart error:', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
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
    private function checkAndAddDomain(
        array &$results,
        string $domainName,
        Tld $tld,
        CartCollection $cartContent,
        bool $isPrimary
    ): void {
        try {
            $isInternational = ! $tld->isLocalTld();

            if ($isInternational) {
                Log::debug('Checking international domain:', ['domain' => $domainName]);
                $checkResult = $this->internationalService->checkAvailability([$domainName]);
                $available = $checkResult['available'] ?? false;
                $reason = $checkResult['reason'] ?? 'Unknown status';
                $results[$domainName] = $this->buildDomainSearchResult->handle(
                    $tld, $domainName, $available, $reason,
                    $this->currentCurrency, $cartContent->has($domainName), $isPrimary, true
                );
                Log::debug('International domain check processed', ['domain' => $domainName, 'available' => $available]);
            } else {
                $eppResults = $this->eppService->checkDomain([$domainName]);

                if ($eppResults !== [] && isset($eppResults[$domainName])) {
                    $result = $eppResults[$domainName];
                    $results[$domainName] = $this->buildDomainSearchResult->handle(
                        $tld, $domainName, $result->available, $result->reason,
                        $this->currentCurrency, $cartContent->has($domainName), $isPrimary, false
                    );
                    Log::debug('Local domain check successful', ['domain' => $domainName, 'available' => $result->available]);
                }
            }
        } catch (Exception $exception) {
            Log::error('Domain check error:', [
                'domain' => $domainName,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /** @return Collection<int, Tld> */
    private function getActiveTlds(): Collection
    {
        return Cache::remember('active_tlds', 3600, fn () => Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->where('status', 'active')
            ->get());
    }

    private function removeLinkedHosting(string $domain): void
    {
        $cartContent = Cart::getContent();

        $target = mb_strtolower($domain);

        foreach ($cartContent as $item) {
            $isHosting = $item->attributes->get('type') === 'hosting';
            $linkedDomain = $item->attributes->get('linked_domain');

            if ($isHosting && $linkedDomain && mb_strtolower((string) $linkedDomain) === $target) {
                Cart::remove($item->id);
            }
        }
    }
}
