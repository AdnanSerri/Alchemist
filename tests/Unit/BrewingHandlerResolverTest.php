<?php

namespace Serri\Alchemist\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Serri\Alchemist\Handlers\MultipleBrewingHandler;
use Serri\Alchemist\Handlers\SingleBrewingHandler;
use Serri\Alchemist\Resolvers\BrewingHandlerResolver;

class BrewingHandlerResolverTest extends TestCase
{
    public function test_it_resolves_the_single_handler(): void
    {
        $this->assertInstanceOf(SingleBrewingHandler::class, BrewingHandlerResolver::resolve('single'));
    }

    public function test_it_resolves_the_multiple_handler(): void
    {
        $this->assertInstanceOf(MultipleBrewingHandler::class, BrewingHandlerResolver::resolve('multiple'));
    }

    public function test_it_rejects_unknown_handlers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BrewingHandlerResolver::resolve('cauldron');
    }
}
