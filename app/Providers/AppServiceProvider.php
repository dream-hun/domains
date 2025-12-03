<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\CurrencyExchangeHelper;
use App\Helpers\DomainSearchHelper;
use App\Models\Setting;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use App\Services\Domain\Testing\FakeEppDomainService;
use App\Services\Domain\Testing\FakeNamecheapDomainService;
use App\Services\ExchangeRateClient;
use Cknow\Money\Money;
use App\Models\HostingCategory;
use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind exchange rate client with config values
        $this->app->singleton(fn (): ExchangeRateClient => new ExchangeRateClient(
            apiKey: config('services.exchange_rate.api_key'),
            baseUrl: config('services.exchange_rate.base_url'),
            timeout: config('services.exchange_rate.timeout'),
            extendedTimeout: config('services.exchange_rate.extended_timeout')
        ));

        // Register domain services based on environment
        $this->registerDomainServices();
    }

    public function boot(): void
    {
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

    /**
     * Register domain services based on the current environment.
     * Uses fake services in testing environment when EPP/Namecheap are not configured.
     */
    private function registerDomainServices(): void
    {
        $useFakeServices = $this->shouldUseFakeDomainServices();

        if ($useFakeServices) {
            $this->registerFakeDomainServices();
        } else {
            $this->registerRealDomainServices();
        }
    }

    /**
     * Determine if fake domain services should be used.
     */
    private function shouldUseFakeDomainServices(): bool
    {
        // Use fake services when in testing environment AND EPP is not configured
        return $this->app->environment('testing') && empty(config('services.epp.host'));
    }

    /**
     * Register fake domain services for testing.
     */
    private function registerFakeDomainServices(): void
    {
        // Singleton fake services so state persists across test assertions
        $this->app->singleton(FakeEppDomainService::class);
        $this->app->singleton(FakeNamecheapDomainService::class);

        // Bind domain search helper with fake services
        $this->app->singleton(fn ($app): DomainSearchHelper => new DomainSearchHelper(
            $app->make(FakeNamecheapDomainService::class),
            $app->make(FakeEppDomainService::class)
        ));

        // Bind domain registration services
        $this->app->bind(DomainRegistrationServiceInterface::class.'.epp', fn ($app): FakeEppDomainService => $app->make(FakeEppDomainService::class));

        $this->app->bind(DomainRegistrationServiceInterface::class.'.namecheap', fn ($app): FakeNamecheapDomainService => $app->make(FakeNamecheapDomainService::class));

        // Named bindings for RegisterDomainAction constructor parameters
        $this->app->bind('epp_domain_service', fn ($app): FakeEppDomainService => $app->make(FakeEppDomainService::class));

        $this->app->bind('namecheap_domain_service', fn ($app): FakeNamecheapDomainService => $app->make(FakeNamecheapDomainService::class));

        // Bind the DomainServiceInterface to FakeNamecheapDomainService by default
        $this->app->bind(DomainServiceInterface::class, FakeNamecheapDomainService::class);

        // Bind DomainRegistrationServiceInterface to FakeEppDomainService by default
        $this->app->bind(DomainRegistrationServiceInterface::class, FakeEppDomainService::class);

        // Also bind the concrete classes to fakes for dependency injection
        $this->app->bind(EppDomainService::class, fn ($app): FakeEppDomainService => $app->make(FakeEppDomainService::class));
        $this->app->bind(NamecheapDomainService::class, fn ($app): FakeNamecheapDomainService => $app->make(FakeNamecheapDomainService::class));
    }

    /**
     * Register real domain services for production/staging.
     */
    private function registerRealDomainServices(): void
    {
        $this->app->singleton(fn ($app): DomainSearchHelper => new DomainSearchHelper(
            $app->make(NamecheapDomainService::class),
            $app->make(EppDomainService::class)
        ));

        // Bind domain registration services
        $this->app->bind(DomainRegistrationServiceInterface::class.'.epp', fn (): EppDomainService => new EppDomainService());

        $this->app->bind(DomainRegistrationServiceInterface::class.'.namecheap', fn (): NamecheapDomainService => new NamecheapDomainService());

        // Named bindings for RegisterDomainAction constructor parameters
        $this->app->bind('epp_domain_service', fn (): EppDomainService => new EppDomainService());

        $this->app->bind('namecheap_domain_service', fn (): NamecheapDomainService => new NamecheapDomainService());

        // Bind the DomainServiceInterface to NamecheapDomainService by default
        $this->app->bind(DomainServiceInterface::class, NamecheapDomainService::class);

        // Bind DomainRegistrationServiceInterface to EppDomainService by default
        // This is used by RenewalService and other services that need domain operations
        $this->app->bind(DomainRegistrationServiceInterface::class, EppDomainService::class);
    }

    /**
     * Register custom Blade directives for currency conversion
     */
    private function registerBladeDirectives(): void
    {
        // @money($amount, $currency) - Create Money object and format it
        Blade::directive('money', fn (string $expression): string => '<?php echo app('.CurrencyExchangeHelper::class.'::class)->formatMoney('.Money::class.'::$expression); ?>');

        // @convert_currency($amount, $from, $to) - Convert and format currency
        Blade::directive('convert_currency', fn (string $expression): string => "<?php
                \$__args = [{$expression}];
                \$__money = currency_convert(\$__args[0], \$__args[1], \$__args[2]);
                echo format_money(\$__money);
            ?>");

        // @usd_to_frw($amount) - Quick USD to RWF conversion (legacy directive name)
        Blade::directive('usd_to_frw', fn (string $expression): string => "<?php
                try {
                    \$__money = usd_to_frw({$expression});
                    echo format_money(\$__money);
                } catch (\App\Exceptions\CurrencyExchangeException \$e) {
                    echo 'N/A';
                }
            ?>");

        // @frw_to_usd($amount) - Quick RWF to USD conversion (legacy directive name)
        Blade::directive('frw_to_usd', fn (string $expression): string => "<?php
                try {
                    \$__money = frw_to_usd({$expression});
                    echo format_money(\$__money);
                } catch (\App\Exceptions\CurrencyExchangeException \$e) {
                    echo 'N/A';
                }
            ?>");
    }
}
