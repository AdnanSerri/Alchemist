<?php

namespace Serri\Alchemist\Contracts;

use Serri\Alchemist\Context\BrewingContext;

interface BrewingHandlerContract
{
    public function brew(BrewingContext $context): array;
}
