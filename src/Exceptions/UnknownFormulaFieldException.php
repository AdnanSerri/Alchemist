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

    public static function forHiddenField(string $field, string $model): self
    {
        return new self(
            "Formula field '$field' is hidden on model [$model] (\$hidden). "
            .'Remove it from the formula, remove it from $hidden, or disable alchemist.respect_hidden.'
        );
    }
}
