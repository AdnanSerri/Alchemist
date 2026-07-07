<?php

namespace Serri\Alchemist\Concerns;

use Serri\Alchemist\Formulas\Formula;
use Serri\Alchemist\Support\FormulaRegistry;

trait HasAlchemyFormulas
{
    private const DEFAULT_FORMULA_CONST_NAME = 'BlankParchment';

    /**
     * Set the model's active formula.
     *
     * @param  array<int|string, mixed>  $formula
     */
    public static function setFormula(array $formula): void
    {
        FormulaRegistry::set(static::class, $formula);
    }

    /**
     * Clear the model's active formula so it falls back to BlankParchment.
     */
    public static function unsetFormula(): void
    {
        FormulaRegistry::forget(static::class);
    }

    /**
     * Get the model's active formula, falling back to the nearest
     * BlankParchment constant.
     *
     * @return array<int|string, mixed>
     */
    public static function formula(): array
    {
        return FormulaRegistry::get(static::class) ?? (static::getDefaultFormulasClass())::BlankParchment;
    }

    private static function getDefaultFormulasClass(): string
    {
        $basename = class_basename(static::class).'Formula';

        foreach (FormulaRegistry::namespaces() as $namespace) {
            $candidate = $namespace.'\\'.$basename;

            if (class_exists($candidate) && static::hasBlankParchmentConstant($candidate)) {
                return $candidate;
            }
        }

        foreach (FormulaRegistry::namespaces() as $namespace) {
            $candidate = $namespace.'\\Formula';

            if (class_exists($candidate) && static::hasBlankParchmentConstant($candidate)) {
                return $candidate;
            }
        }

        return Formula::class;
    }

    private static function hasBlankParchmentConstant(string $class): bool
    {
        $defaultFormulaName = self::DEFAULT_FORMULA_CONST_NAME;

        return defined("$class::$defaultFormulaName");
    }
}
