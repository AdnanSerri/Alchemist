<?php

namespace Serri\Alchemist\Handlers;

use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Contracts\BrewingHandlerContract;

/**
 * @internal
 */
class SingleBrewingHandler implements BrewingHandlerContract
{
    public function brew(BrewingContext $context): array
    {
        $decoction = [];

        foreach ($context->formula() as $element) {
            $decoction = array_merge(
                $decoction,
                AttributeBrewingHandler::brew($context, $context->raw(), $element)
            );
        }

        return $decoction;
    }
}
