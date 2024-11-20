<?php

namespace App\Providers;

use App\Models\ProductHistory;
use App\Observers\ProductHistoryObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $observers = [
        ProductHistory::class => [ProductHistoryObserver::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Schedule the alert check command
        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

        $schedule->command('alerts:check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/alerts.log'));

        // Schedule the integration jobs dispatch
        $schedule->command('integrations:dispatch')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/integrations.log'));
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
