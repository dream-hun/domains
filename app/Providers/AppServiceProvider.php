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
use App\Services\ExchangeRateClient;
use Cknow\Money\Money;
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

    public function boot(): void
    {
        try {
            View::share('settings', Setting::query()->first());
        } catch (Exception) {
            View::share('settings');
        }

        $this->registerBladeDirectives();
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

        // @usd_to_frw($amount) - Quick USD to FRW conversion
        Blade::directive('usd_to_frw', fn (string $expression): string => "<?php
                try {
                    \$__money = usd_to_frw({$expression});
                    echo format_money(\$__money);
                } catch (\App\Exceptions\CurrencyExchangeException \$e) {
                    echo 'N/A';
                }
            ?>");

        // @frw_to_usd($amount) - Quick FRW to USD conversion
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
