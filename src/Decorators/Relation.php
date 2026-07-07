<?php

namespace Serri\Alchemist\Decorators;

use Attribute as Decorator;

#[Decorator(Decorator::TARGET_METHOD)]
final class Relation
{
    public ?string $name;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
