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
     * Namespaces searched (in order) for {Model}Formula / Formula fallbacks.
     * Fed from config('alchemist.formula_namespaces') by the service
     * provider; the default keeps the trait usable without a container.
     *
     * @var array<int, string>
     */
    private static array $namespaces = ['App\\Formulas'];

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

    /**
     * @param  array<int, string>  $namespaces
     */
    public static function setNamespaces(array $namespaces): void
    {
        self::$namespaces = $namespaces === [] ? ['App\\Formulas'] : $namespaces;
    }

    /**
     * @return array<int, string>
     */
    public static function namespaces(): array
    {
        return self::$namespaces;
    }
}
