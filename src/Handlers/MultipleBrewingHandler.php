<?php

namespace Serri\Alchemist\Handlers;

use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Contracts\BrewingHandlerContract;

/**
 * @internal
 */
class MultipleBrewingHandler implements BrewingHandlerContract
{
    public function brew(BrewingContext $context): array
    {
        $decoction = [];

        foreach ($context->raw() as $rawElement) {
            $brewing = [];

            foreach ($context->formula() as $element) {
                $brewing = array_merge(
                    $brewing,
                    AttributeBrewingHandler::brew($context, $rawElement, $element)
                );
            }

            $decoction[] = $brewing;
        }

        return $decoction;
    }
}
