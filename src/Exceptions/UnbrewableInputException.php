<?php

namespace Serri\Alchemist\Exceptions;

class UnbrewableInputException extends AlchemistException
{
    public static function nonModelItems(): self
    {
        return new self('Only Eloquent models can be brewed: the given collection contains non-model items.');
    }

    public static function missingTrait(string $model): self
    {
        return new self(
            "Model [$model] cannot be brewed: it must use the ".
            'Serri\Alchemist\Concerns\HasAlchemyFormulas trait.'
        );
    }
}
