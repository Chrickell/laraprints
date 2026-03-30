<?php

namespace Chrickell\Laraprints\Tests;

use Chrickell\Laraprints\LaraPrintServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Open the viewLaraprints gate for all tests so feature tests can hit
        // dashboard endpoints without needing a real user / email list.
        // Must run here (after boot) so it overwrites the fallback gate
        // registered by LaraPrintServiceProvider::registerDefaultGate().
        Gate::define('viewLaraprints', fn ($user = null) => true);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaraPrintServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config()->set('laraprints.requests.enabled', true);
        config()->set('laraprints.clicks.enabled', true);
        config()->set('laraprints.dashboard.enabled', true);
        config()->set('laraprints.dashboard.middleware', ['web']);
        config()->set('laraprints.queue.connection', 'sync');
        config()->set('laraprints.pruning.page_views_after_days', null);
        config()->set('laraprints.pruning.clicks_after_days', null);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
