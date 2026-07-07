<?php

namespace Serri\Alchemist\Providers;

use Illuminate\Support\ServiceProvider;
use Serri\Alchemist\Services\Alchemist;

class AlchemistServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();

        $this->registerFacades();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/alchemist.php',
            'alchemist'
        );
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../../config/alchemist.php' => config_path('alchemist.php'),
        ], 'alchemist-config');

        $this->publishes([
            __DIR__.'/../../stubs/formula.stub' => app_path('Formulas/Formula.php'),
        ], 'alchemist-formula');
    }

    protected function registerFacades(): void
    {
        $this->app->singleton('Alchemist', function ($app) {
            return new Alchemist;
        });
    }
}
