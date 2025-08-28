<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\DomainSearchHelper;
use App\Models\Setting;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DomainSearchHelper::class, function ($app): DomainSearchHelper {
            return new DomainSearchHelper(
                $app->make(NamecheapDomainService::class),
                $app->make(EppDomainService::class)
            );
        });

        // Bind domain registration services
        $this->app->bind(DomainRegistrationServiceInterface::class.'.epp', function (): EppDomainService {
            return new EppDomainService();
        });

        $this->app->bind(DomainRegistrationServiceInterface::class.'.namecheap', function (): NamecheapDomainService {
            return new NamecheapDomainService();
        });

        // Named bindings for RegisterDomainAction constructor parameters
        $this->app->bind('epp_domain_service', function (): EppDomainService {
            return new EppDomainService();
        });

        $this->app->bind('namecheap_domain_service', function (): NamecheapDomainService {
            return new NamecheapDomainService();
        });
    }

    public function boot(): void
    {
        try {
            View::share('settings', Setting::first());
        } catch (Exception) {
            View::share('settings');
        }
    }
}
