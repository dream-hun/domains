<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TldType;
use App\Helpers\CurrencyHelper;
use App\Helpers\DomainSearchHelper;
use App\Models\Tld;
use Closure;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class DomainSearchPage extends Component
{
    public string $searchedDomain = '';

    public ?array $details = null;

    /** @var array<int, array<string, mixed>> */
    public array $suggestions = [];

    public ?string $errorMessage = null;

    public bool $searchPerformed = false;

    public string $selectedCurrency = '';

    public bool $isSearching = false;

    protected $listeners = [
        'currency-changed' => 'handleCurrencyChanged',
        'currencyChanged' => 'handleCurrencyChanged',
    ];

    public function mount(?string $domain = null): void
    {
        $this->selectedCurrency = CurrencyHelper::getUserCurrency();

        if ($domain !== null && $domain !== '') {
            $this->searchedDomain = $domain;
            $this->search();
        }
    }

    public function handleCurrencyChanged(mixed $currencyOrPayload = null): void
    {
        $currency = $currencyOrPayload === null
            ? ''
            : (is_array($currencyOrPayload)
                ? ($currencyOrPayload['currency'] ?? $currencyOrPayload[0] ?? '')
                : (string) $currencyOrPayload);

        if ($currency === '') {
            return;
        }

        $this->selectedCurrency = mb_strtoupper((string) $currency);
        session(['selected_currency' => $this->selectedCurrency]);
    }

    public function search(): void
    {
        $this->validate([
            'searchedDomain' => [
                'required',
                'string',
                'max:255',
                'min:2',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === '' || $value === '0') {
                        $fail('Please enter a domain name to search.');

                        return;
                    }

                    if (mb_strlen((string) $value) < 2) {
                        $fail('Domain name must be at least 2 characters long.');

                        return;
                    }

                    if (mb_strlen((string) $value) > 253) {
                        $fail('Domain name is too long. Maximum length is 253 characters.');

                        return;
                    }

                    $helper = resolve(DomainSearchHelper::class);
                    if (! $helper->isValidDomainName(mb_trim((string) $value))) {
                        $fail('Invalid domain name format. Use only letters, numbers, dots, and hyphens.');
                    }
                },
            ],
        ], [
            'searchedDomain.required' => 'Please enter a domain name to search.',
        ]);

        $domain = mb_trim($this->searchedDomain);

        $this->reset(['details', 'suggestions', 'errorMessage']);
        $this->isSearching = true;
        $this->searchPerformed = true;

        try {
            $helper = resolve(DomainSearchHelper::class);
            $result = $helper->processDomainSearch($domain);

            if (isset($result['error']) && ! isset($result['details']) && empty($result['suggestions'])) {
                $this->errorMessage = $result['error'];

                return;
            }

            $this->details = $result['details'] ?? null;
            $this->suggestions = $result['suggestions'] ?? [];
            $this->errorMessage = $result['error'] ?? null;
        } catch (Exception $exception) {
            Log::error('Domain search page error', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            $this->errorMessage = 'An unexpected error occurred while searching. Please try again.';
        } finally {
            $this->isSearching = false;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function getDisplayPriceForItem(array $item): string
    {
        $tldId = $item['tld_id'] ?? null;
        if ($tldId !== null) {
            $tld = Tld::query()
                ->with(['tldPricings' => fn ($q) => $q->where('is_current', true)->with('currency')])
                ->find($tldId);

            if ($tld instanceof Tld) {
                return $tld->getFormattedPriceWithFallback('register_price', $this->selectedCurrency);
            }
        }

        return (string) ($item['price'] ?? '');
    }

    public function render(): View
    {
        return view('livewire.domain-search-page', [
            'popularDomains' => $this->getPopularDomainsForDisplay(),
        ]);
    }

    /**
     * @return array{local: array<int, array{tld: string, price: string, currency: string}>, international: array<int, array{tld: string, price: string, currency: string}>}
     */
    private function getPopularDomainsForDisplay(): array
    {
        $popularDomains = [
            'local' => [],
            'international' => [],
        ];

        try {
            $helper = resolve(DomainSearchHelper::class);
            $popularDomains['local'] = $helper->getPopularDomains(TldType::Local, 3, $this->selectedCurrency);
            $popularDomains['international'] = $helper->getPopularDomains(TldType::International, 5, $this->selectedCurrency);
        } catch (Exception $exception) {
            Log::warning('Failed to load popular domains on search page', [
                'error' => $exception->getMessage(),
            ]);
        }

        return $popularDomains;
    }
}
