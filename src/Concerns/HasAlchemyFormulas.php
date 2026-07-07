<?php

namespace Serri\Alchemist\Concerns;

use Serri\Alchemist\Formulas\Formula;

trait HasAlchemyFormulas
{
    private const DEFAULT_FORMULA_CONST_NAME = 'BlankParchment';

    /**
     * static property that modifies the model's JSON representation.
     */
    protected static array $formulas = [];

    /**
     * Set the static property formula array.
     *
     * @param  array  $formula  The formula array.
     *
     * @returns void
     */
    public static function setFormula(array $formula): void
    {
        static::$formulas[static::class] = $formula;
    }

    /**
     * Get the static property formula array.
     */
    public static function formula(): array
    {
        return self::$formulas[static::class] ?? (static::getDefaultFormulasClass())::BlankParchment;
    }

    private static function getDefaultFormulasClass(): string
    {

        $modelDefaultFormulaClass = 'App\\Formulas\\'.(class_basename(static::class)).'Formula';
        $defaultFormulaClass = 'App\\Formulas\\Formula';

        if (class_exists($modelDefaultFormulaClass) and static::hasBlankParchmentConstant($modelDefaultFormulaClass)) {
            return $modelDefaultFormulaClass;
        }

        if (class_exists($defaultFormulaClass) and static::hasBlankParchmentConstant($defaultFormulaClass)) {
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
