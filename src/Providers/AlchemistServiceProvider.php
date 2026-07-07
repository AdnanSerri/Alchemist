<?php

namespace Serri\Alchemist\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Serri\Alchemist\Console\FormulaLintCommand;
use Serri\Alchemist\Console\MakeFormulaCommand;
use Serri\Alchemist\Services\Alchemist;
use Serri\Alchemist\Support\FormulaRegistry;

class AlchemistServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();

        $this->registerOctaneFlush();

        if ($this->app->runningInConsole()) {
            $this->commands([
                FormulaLintCommand::class,
                MakeFormulaCommand::class,
            ]);
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

    /**
     * Octane workers keep static state alive between requests, so a formula
     * set for one user could leak into the next user's response. Flush the
     * registry at every request boundary when Octane is present.
     */
    protected function registerOctaneFlush(): void
    {
        if (! class_exists(RequestReceived::class)) {
            return;
        }

        $this->app['events']->listen(
            RequestReceived::class,
            static fn () => FormulaRegistry::flush()
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
}
