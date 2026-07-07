<?php

namespace Serri\Alchemist\Services;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SCollection;
use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Exceptions\UnbrewableInputException;
use Serri\Alchemist\Resolvers\BrewingHandlerResolver;
use Serri\Alchemist\Support\BrewingConfigLoader;
use Serri\Alchemist\Support\Druid;

class Alchemist
{
    /**
     * Brewing state lives in the BrewingContext local to each call (never on
     * the instance), so nested brews of relations can safely re-enter the
     * same instance.
     *
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model|null  $collection
     * @return array<int|string, mixed>
     *
     * @throws UnbrewableInputException
     */
    public function brew(ECollection|SCollection|Model|null $collection): array
    {
        if (! $collection instanceof Model && ($collection === null || $collection->isEmpty())) {
            return [];
        }

        $configuration = BrewingConfigLoader::load();

        $context = $this->initContext($collection, $configuration);

        $handler = BrewingHandlerResolver::resolve($context->handler());

        $context->setDecoction($handler->brew($context));

        return $context->decoction();
    }

    /**
     * The paginator is mutated in place: its models are replaced by their
     * brewed arrays while the pagination metadata is preserved.
     *
     * @param  LengthAwarePaginator<int|string, mixed>  $paginator
     * @return LengthAwarePaginator<int|string, mixed>
     */
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
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model  $collection
     * @param  array{formulas_folder_path: string, ingredients: array<int, class-string>}  $configuration
     *
     * @throws UnbrewableInputException
     */
    private function initContext(ECollection|SCollection|Model $collection, array $configuration): BrewingContext
    {
        $examined = Druid::examine($collection);

        if ($examined === null) {
            throw UnbrewableInputException::nonModelItems();
        }

        [$sample, $handler] = $examined;

        [$attributes, $formula] = Druid::extract($sample, $configuration['ingredients']);

        return new BrewingContext(
            raw: $collection,
            ingredients: $configuration['ingredients'],
            attributes: $attributes,
            formula: $formula,
            handler: $handler,
            sample: $sample,
        );
    }
}
