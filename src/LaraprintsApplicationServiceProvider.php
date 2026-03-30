<?php

namespace Chrickell\Laraprints;

use Illuminate\Support\ServiceProvider;

abstract class LaraprintsApplicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->gate();
    }

    /**
     * Define the gate that guards the laraprints dashboard.
     *
     * Override this method in your published LaraprintsServiceProvider
     * to control who can access the dashboard.
     */
    abstract protected function gate(): void;
}
