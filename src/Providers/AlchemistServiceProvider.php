<?php

namespace Serri\Alchemist\Providers;

use Illuminate\Support\ServiceProvider;
use Serri\Alchemist\Console\MakeFormulaCommand;
use Serri\Alchemist\Services\Alchemist;

class AlchemistServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();

        if ($this->app->runningInConsole()) {
            $this->commands([MakeFormulaCommand::class]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/alchemist.php',
            'alchemist'
        );

        $this->app->singleton(Alchemist::class);

        // Backwards-compatible accessor for the facade and app('Alchemist').
        $this->app->alias(Alchemist::class, 'Alchemist');
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
}
