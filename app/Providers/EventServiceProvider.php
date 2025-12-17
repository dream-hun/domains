<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ExchangeRatesUpdated;
use App\Listeners\ClearUserCarts;
use App\Services\Audit\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [

        ExchangeRatesUpdated::class => [
            ClearUserCarts::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        foreach (config('activitylog.log_events', []) as $eloquentEvent) {
            Event::listen(sprintf('eloquent.%s: *', $eloquentEvent), function (string $event, array $payload) use ($eloquentEvent): void {
                $model = $payload[0] ?? null;

                if (! $model instanceof Model) {
                    return;
                }

                resolve(ActivityLogger::class)->logModelEvent($eloquentEvent, $model);
            });
        }

        Event::listen(Login::class, function (Login $event): void {
            resolve(ActivityLogger::class)->logAuthEvent('login', $event->user, [
                'guard' => $event->guard,
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            resolve(ActivityLogger::class)->logAuthEvent('logout', $event->user, [
                'guard' => $event->guard,
            ]);
        });

        Event::listen(Registered::class, function (Registered $event): void {
            resolve(ActivityLogger::class)->logAuthEvent('registered', $event->user);
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            resolve(ActivityLogger::class)->logAuthEvent('password_reset', $event->user);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            resolve(ActivityLogger::class)->logAuthEvent('login_failed', $event->user, [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ]);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
