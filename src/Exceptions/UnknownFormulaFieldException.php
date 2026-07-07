<?php

namespace Serri\Alchemist\Exceptions;

class UnknownFormulaFieldException extends AlchemistException
{
    public static function forField(string $field, string $model): self
    {
        return new self(
            "Formula field '$field' is not exposed on model [$model]. "
            .'Expose it via $fillable / $guarded, or mark a method with #[Mutagen] or #[Relation].'
        );
    }
}
