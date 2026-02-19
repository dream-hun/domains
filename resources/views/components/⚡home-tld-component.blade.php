<?php

use App\Helpers\CurrencyHelper;
use App\Models\Tld;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    protected $listeners = [
        'currencyChanged' => '$refresh',
        'currency-changed' => '$refresh',
    ];

    #[Computed]
    public function domainComparePrices(): array
    {
        return $this->buildDomainComparePrices();
    }

    private function buildDomainComparePrices(): array
    {
        $userCurrencyCode = CurrencyHelper::getUserCurrency();
        $domainCompareTlds = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->whereIn('name', ['.com', '.net', '.info', '.org'])
            ->get()
            ->keyBy(fn (Tld $tld): string => mb_ltrim($tld->name, '.'));

        $domainComparePrices = [];
        foreach (['com', 'net', 'info', 'org'] as $ext) {
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
                        <form action="{{ route('domains.search') }}" class="domain-checker" method="post"
                            data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">
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
                                    <li data-sal="slide-down" data-sal-delay="500" data-sal-duration="800">Compare:
                                    </li>
                                    <li data-sal="slide-down" data-sal-delay="600" data-sal-duration="800"><span
                                            class="ext">.com</span> {{ $this->domainComparePrices['com'] ? 'only ' . $this->domainComparePrices['com'] : '—' }}
                                    </li>
                                    <li data-sal="slide-down" data-sal-delay="700" data-sal-duration="800"><span
                                            class="ext">.net</span> {{ $this->domainComparePrices['net'] ? 'only ' . $this->domainComparePrices['net'] : '—' }}
                                    </li>
                                    <li data-sal="slide-down" data-sal-delay="800" data-sal-duration="800"><span
                                            class="ext">.info</span> {{ $this->domainComparePrices['info'] ? 'only ' . $this->domainComparePrices['info'] : '—' }}
                                    </li>
                                    <li data-sal="slide-down" data-sal-delay="900" data-sal-duration="800"><span
                                            class="ext">.org</span> {{ $this->domainComparePrices['org'] ? 'only ' . $this->domainComparePrices['org'] : '—' }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>