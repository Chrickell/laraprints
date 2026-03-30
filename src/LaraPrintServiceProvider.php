<?php

namespace Chrickell\Laraprints;

use Chrickell\Laraprints\Console\Commands\AggregateDailyStats;
use Chrickell\Laraprints\Support\ConfigValidator;
use Chrickell\Laraprints\Console\Commands\AnonymizeData;
use Chrickell\Laraprints\Console\Commands\CheckInstall;
use Chrickell\Laraprints\Console\InstallCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class LaraPrintServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laraprints.php', 'laraprints');

        // If the application has published its own LaraPrintServiceProvider
        // (via `php artisan vendor:publish --tag=laraprints-provider`), register
        // it automatically — just like Horizon does with HorizonServiceProvider.
        if (class_exists(\App\Providers\LaraprintsServiceProvider::class)) {
            $this->app->register(\App\Providers\LaraprintsServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/laraprints.php' => config_path('laraprints.php'),
        ], 'laraprints-config');

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'laraprints-migrations');

        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/laraprints'),
        ], 'laraprints-components');

        $this->publishes([
            __DIR__ . '/../stubs/LaraPrintServiceProvider.stub' => app_path('Providers/LaraprintsServiceProvider.php'),
        ], 'laraprints-provider');

        if (! $this->app->runningUnitTests()) {
            ConfigValidator::validate();
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerDefaultGate();
        $this->registerTrackingRoute();
        $this->registerDashboardRoutes();
        $this->registerMiddleware();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                AggregateDailyStats::class,
                CheckInstall::class,
                AnonymizeData::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('laraprints:aggregate-daily')->dailyAt('00:05');
        });
    }

    /**
     * Register the fallback viewLaraprints gate.
     *
     * This gate is only defined when the application has NOT published its own
     * LaraPrintServiceProvider (which defines the gate with real email checks).
     * The fallback allows access in local environments only, so the dashboard
     * is usable during development without any additional configuration.
     */
    protected function registerDefaultGate(): void
    {
        if (! Gate::has('viewLaraprints')) {
            Gate::define('viewLaraprints', function ($user = null) {
                return $this->app->environment('local');
            });
        }
    }

    protected function registerTrackingRoute(): void
    {
        $clicksEnabled = config('laraprints.clicks.enabled', true);
        $eventsEnabled = config('laraprints.events.enabled', true);

        if ($clicksEnabled || $eventsEnabled) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }

    protected function registerDashboardRoutes(): void
    {
        if (config('laraprints.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/dashboard.php');
        }
    }

    protected function registerMiddleware(): void
    {
        if (config('laraprints.requests.enabled', true)) {
            $this->app['router']->aliasMiddleware(
                'track.requests',
                \Chrickell\Laraprints\Http\Middleware\TrackPageViews::class
            );

            if (config('laraprints.requests.auto_register_middleware', false)) {
                $this->app->make(Kernel::class)
                    ->appendMiddlewareToGroup('web', \Chrickell\Laraprints\Http\Middleware\TrackPageViews::class);
            }
        }
    }

    // Pruning is handled by the MassPrunable trait on PageView, Click, DailyStat, Session, and LpEvent.
    // Models only return prunable records when the relevant laraprints.pruning.* / laraprints.analytics.*
    // / laraprints.events.prune_after_days config key is set to a positive integer.
    // Tables: laraprints_page_views, laraprints_clicks, laraprints_daily_stats, laraprints_sessions, laraprints_events
    // Run: php artisan model:prune --model="Chrickell\Laraprints\Models\PageView"
    //       php artisan model:prune --model="Chrickell\Laraprints\Models\Click"
    //       php artisan model:prune --model="Chrickell\Laraprints\Models\DailyStat"
    //       php artisan model:prune --model="Chrickell\Laraprints\Models\Session"
    //       php artisan model:prune --model="Chrickell\Laraprints\Models\LpEvent"
    // Or add model:prune to your scheduler to run automatically.
}
