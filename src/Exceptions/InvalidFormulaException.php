<?php

namespace Serri\Alchemist\Exceptions;

class InvalidFormulaException extends AlchemistException
{
    public static function malformedEntry(int|string $key, mixed $value): self
    {
        $type = get_debug_type($value);

        return new self(
            "Malformed formula entry at key [$key] ($type). "
            ."Formula entries must be field names ('title') or nested specs ('relation' => [...])."
        );
    }

    public static function nestedFormulaNotSupported(string $field, string $ingredient): self
    {
        return new self(
            "Field '$field' declares a nested formula, but its ingredient [$ingredient] "
            .'cannot brew one. Nested formulas only apply to relation-like ingredients.'
        );
    }
}
