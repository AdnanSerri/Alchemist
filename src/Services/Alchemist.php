<?php

namespace Serri\Alchemist\Services;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SCollection;
use ReflectionException;
use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Resolvers\BrewingHandlerResolver;
use Serri\Alchemist\Support\BrewingConfigLoader;
use Serri\Alchemist\Support\Druid;

class Alchemist
{
    private array $configuration;

    private BrewingContext $context;

    public function brew(ECollection|SCollection|Model|null $collection): array
    {
        if (! $collection or (($collection instanceof ECollection or $collection instanceof SCollection) and $collection->isEmpty())) {
            return [];
        }

        // Load 'alchemist' configuration.
        $this->configuration = BrewingConfigLoader::load();

        // Init context.
        $this->context = $this->initContext($collection);

        // Resolve brewing handler.
        $handler = BrewingHandlerResolver::resolve($this->context->handler());

        // Apply handler and set it back to the context.
        $this->context->setDecoction($handler->brew($this->context));

        return $this->context->decoction();
    }

    public function brewBatch(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        if (empty($paginator->items())) {
            return $paginator;
        }

        $brewing = $this->brew(collect($paginator->items()));

        $paginator->setCollection(collect($brewing));

        return $paginator;
    }

    /**
     * @throws ReflectionException
     */
    private function initContext(ECollection|SCollection|Model|null $collection): BrewingContext
    {

        // Define $sample + $handler.
        // TODO: null check.
        [$sample, $handler] = Druid::examine($collection);

        // Extract $attributes + $formula.
        [$attributes, $formula] = Druid::extract($sample, $this->configuration['ingredients']);

        return new BrewingContext(
            raw: $collection,
            ingredients: $this->configuration['ingredients'],
            attributes: $attributes,
            formula: $formula,
            handler: $handler,
            sample: $sample,
        );
    }
}
