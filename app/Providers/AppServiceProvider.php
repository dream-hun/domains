<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\DomainSearchHelper;
use App\Models\DomainPriceCurrency;
use App\Models\HostingCategory;
use App\Models\HostingPlanPrice;
use App\Models\Setting;
use App\Models\Tld;
use App\Observers\DomainPriceCurrencyObserver;
use App\Observers\DomainPriceObserver;
use App\Observers\HostingPlanPriceHistoryObserver;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDomainServices();
    }

    public function boot(): void
    {

        Model::preventLazyLoading();
        Tld::observe(DomainPriceObserver::class);
        DomainPriceCurrency::observe(DomainPriceCurrencyObserver::class);
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

    
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }

  
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
