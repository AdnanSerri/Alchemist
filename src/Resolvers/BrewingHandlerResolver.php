<?php

namespace Serri\Alchemist\Resolvers;

use InvalidArgumentException;
use Serri\Alchemist\Contracts\BrewingHandlerContract;
use Serri\Alchemist\Handlers\MultipleBrewingHandler;
use Serri\Alchemist\Handlers\SingleBrewingHandler;

/**
 * @internal
 */
class BrewingHandlerResolver
{
    public static function resolve(string $handler): BrewingHandlerContract
    {
        return match ($handler) {
            'single' => new SingleBrewingHandler,
            'multiple' => new MultipleBrewingHandler,
            default => throw new InvalidArgumentException("Unknown brewing handler: $handler"),
        };
    }
}
