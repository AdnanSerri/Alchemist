<?php

namespace Serri\Alchemist\Handlers;

use Exception;
use Serri\Alchemist\Context\BrewingContext;

/**
 * @internal
 */
class AttributeBrewingHandler
{
    /**
     * @throws Exception
     */
    public static function brew(BrewingContext $context, mixed $brewing, string $element): array
    {
        $ingredientClass = $context->attributes()[$element];

        if (! class_exists($ingredientClass) or ! method_exists($ingredientClass, 'infuse')) {
            throw new Exception("Ingredient called '$ingredientClass' is not existed, or $ingredientClass::infuse is not existed");
        }

        return $ingredientClass::infuse($element, $brewing);
    }
}
