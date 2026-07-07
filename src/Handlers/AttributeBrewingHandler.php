<?php

namespace Serri\Alchemist\Handlers;

use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Contracts\AcceptsNestedFormula;
use Serri\Alchemist\Exceptions\InvalidFormulaException;
use Serri\Alchemist\Exceptions\InvalidIngredientException;
use Serri\Alchemist\Exceptions\UnknownFormulaFieldException;

/**
 * @internal
 */
class AttributeBrewingHandler
{
    /**
     * @param  array<int|string, mixed>|null  $nestedFormula
     * @return array<string, mixed>
     *
     * @throws UnknownFormulaFieldException
     * @throws InvalidIngredientException
     * @throws InvalidFormulaException
     */
    public static function brew(
        BrewingContext $context,
        mixed $brewing,
        string $element,
        ?array $nestedFormula = null,
    ): array {
        $ingredientClass = $context->attributes()[$element]
            ?? throw UnknownFormulaFieldException::forField($element, get_class($context->sample()));

        if (! class_exists($ingredientClass) || ! method_exists($ingredientClass, 'infuse')) {
            throw InvalidIngredientException::forClass($ingredientClass);
        }

        if ($nestedFormula === null) {
            return $ingredientClass::infuse($element, $brewing);
        }

        if (! is_subclass_of($ingredientClass, AcceptsNestedFormula::class)) {
            throw InvalidFormulaException::nestedFormulaNotSupported($element, $ingredientClass);
        }

        return $ingredientClass::infuseWithFormula($element, $brewing, $nestedFormula);
    }
}
