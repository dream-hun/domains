<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\DomainSearchHelper;
use App\Models\DomainPrice;
use App\Models\HostingCategory;
use App\Models\Setting;
use App\Observers\DomainPriceObserver;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use App\Services\ExchangeRateClient;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        $this->app->singleton(fn (): ExchangeRateClient => new ExchangeRateClient(
            apiKey: config('services.exchange_rate.api_key'),
            baseUrl: config('services.exchange_rate.base_url'),
            timeout: config('services.exchange_rate.timeout'),
            extendedTimeout: config('services.exchange_rate.extended_timeout')
        ));

        $this->registerDomainServices();
    }

    public function boot(): void
    {
        DomainPrice::observe(DomainPriceObserver::class);
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
