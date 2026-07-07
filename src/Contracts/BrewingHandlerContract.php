<?php

namespace Serri\Alchemist\Contracts;

use Serri\Alchemist\Context\BrewingContext;

interface BrewingHandlerContract
{
    /**
     * @return array<int|string, mixed>
     */
    public function brew(BrewingContext $context): array;
}
