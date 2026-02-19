<?php

use App\Helpers\CurrencyHelper;
use App\Models\Tld;
use Livewire\Component;

new class extends Component
{
    private const COMPARE_TLDS = ['.com', '.net', '.info', '.org'];

    public array $domainComparePrices = [];

    protected $listeners = [
        'currencyChanged' => 'handleCurrencyChanged',
        'currency-changed' => 'handleCurrencyChanged',
    ];

    public function mount(): void
    {
        $this->domainComparePrices = $this->buildDomainComparePrices();
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->domainComparePrices = $this->buildDomainComparePrices();
    }

    private function buildDomainComparePrices(): array
    {
        $userCurrencyCode = CurrencyHelper::getUserCurrency();
        $domainCompareTlds = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->whereIn('name', self::COMPARE_TLDS)
            ->get()
            ->keyBy(fn (Tld $tld): string => mb_ltrim($tld->name, '.'));

        $domainComparePrices = [];
        foreach (self::COMPARE_TLDS as $tldName) {
            $ext = mb_ltrim($tldName, '.');
            $tld = $domainCompareTlds->get($ext);
            $domainComparePrices[$ext] = $tld
                ? $tld->getFormattedPriceWithFallback('register_price', $userCurrencyCode)
                : null;
        }

        return $domainComparePrices;
    }
}
?>

<div class="rts-domain-finder">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="rts-domain-finder__content domain-finder-bg">
                        <h3 data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">A name that looks good
                            on
                            a billboard.</h3>
                        <form action="{{ route('domains.search') }}" class="domain-checker" method="post">
                            @csrf
                            <input type="text" id="domain-name" name="domain"
                                placeholder="Register a domain name to start" required>

                            <button type="submit" aria-label="register domain" name="domain_type">search
                                domain
                            </button>
                        </form>
                        <div class="compare">
                            <div class="compare__list">
                                <ul>
                                    <li data-sal="slide-down">Compare:
                                    </li>
                                    <li data-sal="slide-down"><span
                                            class="ext">.com</span> {{ $domainComparePrices['com'] ? 'only ' . $domainComparePrices['com'] : '—' }}
                                    </li>
                                    <li data-sal="slide-down"><span
                                            class="ext">.net</span> {{ $domainComparePrices['net'] ? 'only ' . $domainComparePrices['net'] : '—' }}
                                    </li>
                                    <li data-sal="slide-down"><span
                                            class="ext">.info</span> {{ $domainComparePrices['info'] ? 'only ' . $domainComparePrices['info'] : '—' }}
                                    </li>
                                    <li data-sal="slide-down"><span
                                            class="ext">.org</span> {{ $domainComparePrices['org'] ? 'only ' . $domainComparePrices['org'] : '—' }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>