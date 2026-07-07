<?php

namespace Serri\Alchemist\Handlers;

use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Exceptions\InvalidIngredientException;
use Serri\Alchemist\Exceptions\UnknownFormulaFieldException;

/**
 * @internal
 */
class AttributeBrewingHandler
{
    /**
     * @return array<string, mixed>
     *
     * @throws UnknownFormulaFieldException
     * @throws InvalidIngredientException
     */
    public static function brew(BrewingContext $context, mixed $brewing, string $element): array
    {
        $ingredientClass = $context->attributes()[$element]
            ?? throw UnknownFormulaFieldException::forField($element, get_class($context->sample()));

        if (! class_exists($ingredientClass) || ! method_exists($ingredientClass, 'infuse')) {
            throw InvalidIngredientException::forClass($ingredientClass);
        }

        return $ingredientClass::infuse($element, $brewing);
    }
}
