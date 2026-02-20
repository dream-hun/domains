<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\CurrencyHelper;
use App\Helpers\DomainSearchHelper;
use App\Models\HostingCategory;
use App\Models\HostingPlanPrice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\TldPricing;
use App\Observers\HostingPlanPriceHistoryObserver;
use App\Observers\PaymentObserver;
use App\Observers\TldPricingObserver;
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
        Model::automaticallyEagerLoadRelationships();
        Model::preventLazyLoading();
        HostingPlanPrice::observe(HostingPlanPriceHistoryObserver::class);
        TldPricing::observe(TldPricingObserver::class);
        Payment::observe(PaymentObserver::class);
        try {
            View::share('settings', Setting::query()->first());
        } catch (Exception) {
            View::share('settings');
        }

        try {
            $hostings = HostingCategory::getActiveCategories();
            View::share('hostings', $hostings);
        } catch (Exception) {
            View::share('hostings', []);
        }

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Blade::directive('price', function (string $expression): string {
            $inner = mb_trim($expression, ' ()');

            return sprintf('<?php $__p = [%s]; echo '.CurrencyHelper::class.'::formatMoney((float) ($__p[0] ?? 0), (string) (mb_strtoupper((string) ($__p[1] ?? \'USD\')) === \'FRW\' ? \'RWF\' : ($__p[1] ?? \'USD\'))); ?>', $inner);
        });
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
