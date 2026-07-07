<?php

namespace Serri\Alchemist\Support;

/**
 * Central store for the active formulas set via Model::setFormula().
 *
 * Keeping the state in one place (instead of a static property copied into
 * every model using the trait) makes it flushable as a whole — the service
 * provider flushes it at every Octane request boundary so formulas can
 * never leak between requests in long-lived workers.
 */
final class FormulaRegistry
{
    /**
     * @var array<class-string, array<int|string, mixed>>
     */
    private static array $formulas = [];

    /**
     * @param  array<int|string, mixed>  $formula
     */
    public static function set(string $model, array $formula): void
    {
        self::$formulas[$model] = $formula;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public static function get(string $model): ?array
    {
        return self::$formulas[$model] ?? null;
    }

    public static function forget(string $model): void
    {
        unset(self::$formulas[$model]);
    }

    public static function flush(): void
    {
        self::$formulas = [];
    }
}
