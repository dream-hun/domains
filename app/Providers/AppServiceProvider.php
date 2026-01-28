<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Currency\UpdateExchangeRatesAction;
use App\Contracts\Currency\CurrencyConverterContract;
use App\Contracts\Currency\CurrencyFormatterContract;
use App\Contracts\Currency\ExchangeRateProviderContract;
use App\Helpers\CurrencyExchangeHelper;
use App\Helpers\DomainSearchHelper;
use App\Models\DomainPrice;
use App\Models\HostingCategory;
use App\Models\HostingPlanPrice;
use App\Models\Setting;
use App\Observers\DomainPriceObserver;
use App\Observers\HostingPlanPriceHistoryObserver;
use App\Services\Currency\CurrencyConverter;
use App\Services\Currency\CurrencyFormatter;
use App\Services\Currency\ExchangeRateProvider;
use App\Services\CurrencyService;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use App\Services\ExchangeRateClient;
use App\Services\PriceFormatter;
use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCurrencyServices();
        $this->registerDomainServices();
    }

    public function boot(): void
    {
        DomainPrice::observe(DomainPriceObserver::class);
        HostingPlanPrice::observe(HostingPlanPriceHistoryObserver::class);
        try {
            View::share('settings', Setting::query()->first());
        } catch (Exception) {
            View::share('settings');
        }

        try {
            $hostings = HostingCategory::query()->select(['name', 'slug', 'icon'])->where('status', 'active')->get();
            View::share('hostings', $hostings);
        } catch (Exception) {
            View::share('hostings', []);
        }

        $this->registerBladeDirectives();
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('price', fn (string $expression): string => "<?php
                \$__priceArgs = [{$expression}];
                if (count(\$__priceArgs) === 1 && \$__priceArgs[0] instanceof \\App\\ValueObjects\\Price) {
                    echo \$__priceArgs[0]->format();
                } elseif (count(\$__priceArgs) >= 2) {
                    echo app(\\App\\Services\\PriceFormatter::class)->format(\$__priceArgs[0], \$__priceArgs[1]);
                } else {
                    echo '';
                }
            ?>");

        Blade::directive('priceMinor', fn (string $expression): string => sprintf('<?php echo app('.PriceFormatter::class.'::class)->formatFromMinorUnits(%s); ?>', $expression));

        Blade::directive('currencySymbol', fn (string $expression): string => sprintf('<?php echo app('.PriceFormatter::class.'::class)->getSymbol(%s); ?>', $expression));
    }

    /**
     * Register currency-related services and contracts.
     */
    private function registerCurrencyServices(): void
    {
        // Exchange rate client singleton
        $this->app->singleton(ExchangeRateClient::class, fn (): ExchangeRateClient => new ExchangeRateClient(
            apiKey: config('services.exchange_rate.api_key'),
            baseUrl: config('services.exchange_rate.base_url'),
            timeout: config('services.exchange_rate.timeout'),
            extendedTimeout: config('services.exchange_rate.extended_timeout')
        ));

        // Bind contracts to new implementations
        $this->app->singleton(CurrencyFormatterContract::class, CurrencyFormatter::class);
        $this->app->singleton(ExchangeRateProviderContract::class, ExchangeRateProvider::class);
        $this->app->singleton(CurrencyConverterContract::class, CurrencyConverter::class);

        // Backward compatibility: alias CurrencyService to new converter
        $this->app->singleton(CurrencyService::class, fn ($app): CurrencyService => new CurrencyService(
            $app->make(UpdateExchangeRatesAction::class),
            $app->make(CurrencyExchangeHelper::class),
            $app->make(PriceFormatter::class)
        ));
    }

    /**
     * Register real domain services for production/staging.
     */
    private function registerDomainServices(): void
    {
        $this->app->singleton(fn ($app): DomainSearchHelper => new DomainSearchHelper(
            $app->make(NamecheapDomainService::class),
            $app->make(EppDomainService::class)
        ));

        $this->app->bind(DomainRegistrationServiceInterface::class.'.epp', fn (): EppDomainService => new EppDomainService());

        $this->app->bind(DomainRegistrationServiceInterface::class.'.namecheap', fn (): NamecheapDomainService => new NamecheapDomainService());

        $this->app->bind('epp_domain_service', fn (): EppDomainService => new EppDomainService());

        $this->app->bind('namecheap_domain_service', fn (): NamecheapDomainService => new NamecheapDomainService());

        $this->app->bind(DomainServiceInterface::class, NamecheapDomainService::class);

        $this->app->bind(DomainRegistrationServiceInterface::class, EppDomainService::class);
    }
}
