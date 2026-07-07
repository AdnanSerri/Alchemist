<?php

namespace Serri\Alchemist\Concerns;

use Serri\Alchemist\Formulas\Formula;

trait HasAlchemyFormulas
{
    private const DEFAULT_FORMULA_CONST_NAME = 'BlankParchment';

    /**
     * Active formulas, keyed by model class.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static array $formulas = [];

    /**
     * Set the model's active formula.
     *
     * @param  array<int, string>  $formula
     */
    public static function setFormula(array $formula): void
    {
        static::$formulas[static::class] = $formula;
    }

    /**
     * Clear the model's active formula so it falls back to BlankParchment.
     */
    public static function unsetFormula(): void
    {
        unset(static::$formulas[static::class]);
    }

    /**
     * Get the model's active formula, falling back to the nearest
     * BlankParchment constant.
     *
     * @return array<int, string>
     */
    public static function formula(): array
    {
        return self::$formulas[static::class] ?? (static::getDefaultFormulasClass())::BlankParchment;
    }

    private static function getDefaultFormulasClass(): string
    {
        $modelDefaultFormulaClass = 'App\\Formulas\\'.(class_basename(static::class)).'Formula';
        $defaultFormulaClass = 'App\\Formulas\\Formula';

        if (class_exists($modelDefaultFormulaClass) && static::hasBlankParchmentConstant($modelDefaultFormulaClass)) {
            return $modelDefaultFormulaClass;
        }

        if (class_exists($defaultFormulaClass) && static::hasBlankParchmentConstant($defaultFormulaClass)) {
            return $defaultFormulaClass;
        }

        return Formula::class;
    }

    private static function hasBlankParchmentConstant(string $class): bool
    {
        $defaultFormulaName = self::DEFAULT_FORMULA_CONST_NAME;

        return defined("$class::$defaultFormulaName");
    }
}
