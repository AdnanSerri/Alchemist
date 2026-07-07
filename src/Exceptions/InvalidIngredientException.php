<?php

namespace Serri\Alchemist\Exceptions;

class InvalidIngredientException extends AlchemistException
{
    public static function forClass(string $ingredient): self
    {
        return new self(
            "Ingredient [$ingredient] does not exist or does not implement a static infuse() method."
        );
    }
}
