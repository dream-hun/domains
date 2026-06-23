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
use App\Services\Domain\CircuitBreaker;
use App\Services\Domain\DomainAvailabilityCache;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\DomainRouter;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use App\Services\IdempotencyService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IdempotencyService::class);
        $this->registerDomainServices();
    }

    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();
        HostingPlanPrice::observe(HostingPlanPriceHistoryObserver::class);
        TldPricing::observe(TldPricingObserver::class);
        Payment::observe(PaymentObserver::class);
        try {
            View::share('settings', Cache::remember('app_settings', 3600, fn () => Setting::query()->first()));
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

        $this->configureRateLimiters();

        Blade::directive('price', function (string $expression): string {
            $inner = mb_trim($expression, ' ()');

            return sprintf('<?php $__p = [%s]; echo '.CurrencyHelper::class.'::formatMoney((float) ($__p[0] ?? 0), (string) (mb_strtoupper((string) ($__p[1] ?? \'USD\')) === \'FRW\' ? \'RWF\' : ($__p[1] ?? \'USD\'))); ?>', $inner);
        });
    }

    private function registerDomainServices(): void
    {
        $this->app->singleton(CircuitBreaker::class);
        $this->app->singleton(DomainAvailabilityCache::class);

        $this->app->singleton(EppDomainService::class, fn (Application $app): EppDomainService => new EppDomainService(
            $app->make(CircuitBreaker::class),
        ));

        $this->app->singleton(NamecheapDomainService::class, fn (Application $app): NamecheapDomainService => new NamecheapDomainService(
            $app->make(CircuitBreaker::class),
        ));

        $this->app->bind(DomainServiceInterface::class, NamecheapDomainService::class);
        $this->app->bind(DomainRegistrationServiceInterface::class, EppDomainService::class);

        $this->app->singleton(DomainRouter::class, fn (Application $app): DomainRouter => new DomainRouter(
            eppService: $app->make(EppDomainService::class),
            namecheapService: $app->make(NamecheapDomainService::class),
        ));

        $this->app->singleton(DomainSearchHelper::class, fn (Application $app): DomainSearchHelper => new DomainSearchHelper(
            $app->make(DomainRouter::class),
            $app->make(DomainAvailabilityCache::class),
        ));
    }

    private function configureRateLimiters(): void
    {
        // Domain search: 30 per minute per IP (unauthenticated) or per user
        RateLimiter::for('domain-search', fn (Request $request): Limit => $request->user()
            ? Limit::perMinute(60)->by($request->user()->id)
            : Limit::perMinute(30)->by($request->ip()));

        // Payment endpoints: 10 per minute per user to slow brute-force card testing
        RateLimiter::for('payments', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()?->id ?? $request->ip()));
    }
}
