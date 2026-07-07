<?php

namespace Serri\Alchemist\Services;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection as SCollection;
use Serri\Alchemist\Context\BrewingContext;
use Serri\Alchemist\Exceptions\UnbrewableInputException;
use Serri\Alchemist\Resolvers\BrewingHandlerResolver;
use Serri\Alchemist\Support\BrewingConfigLoader;
use Serri\Alchemist\Support\Druid;
use Serri\Alchemist\Support\FormulaParser;

class Alchemist
{
    /**
     * Transform a model or collection into an array.
     *
     * When $formula is given it wins over the model's active formula for
     * this call only — global state is never touched. Formula entries may
     * be plain field names or nested specs ('relation' => [...]).
     *
     * Brewing state lives in the BrewingContext local to each call (never on
     * the instance), so nested brews of relations can safely re-enter the
     * same instance.
     *
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model|null  $collection
     * @param  array<int|string, mixed>|null  $formula
     * @return array<int|string, mixed>
     *
     * @throws UnbrewableInputException
     */
    public function brew(ECollection|SCollection|Model|null $collection, ?array $formula = null): array
    {
        if (! $collection instanceof Model && ($collection === null || $collection->isEmpty())) {
            return [];
        }

        $configuration = BrewingConfigLoader::load();

        $context = $this->initContext($collection, $configuration, $formula);

        $handler = BrewingHandlerResolver::resolve($context->handler());

        $context->setDecoction($handler->brew($context));

        return $context->decoction();
    }

    /**
     * The paginator is mutated in place: its models are replaced by their
     * brewed arrays while the pagination metadata is preserved. Accepts
     * length-aware, simple, and cursor paginators.
     *
     * @template TPaginator of AbstractPaginator<int|string, mixed>|AbstractCursorPaginator<int|string, mixed>
     *
     * @param  TPaginator  $paginator
     * @param  array<int|string, mixed>|null  $formula
     * @return TPaginator
     */
    public function brewBatch(AbstractPaginator|AbstractCursorPaginator $paginator, ?array $formula = null): AbstractPaginator|AbstractCursorPaginator
    {
        if (empty($paginator->items())) {
            return $paginator;
        }

        $brewing = $this->brew(collect($paginator->items()), $formula);

        $paginator->setCollection(collect($brewing));

        return $paginator;
    }

    /**
     * Brew straight into a JsonResponse. Paginators keep their pagination
     * envelope; models and collections return the brewed array.
     *
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model|AbstractPaginator<int|string, mixed>|AbstractCursorPaginator<int|string, mixed>|null  $input
     * @param  array<int|string, mixed>|null  $formula
     * @param  array<string, string>  $headers
     */
    public function response(
        ECollection|SCollection|Model|AbstractPaginator|AbstractCursorPaginator|null $input,
        ?array $formula = null,
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        $payload = $input instanceof AbstractPaginator || $input instanceof AbstractCursorPaginator
            ? $this->brewBatch($input, $formula)
            : $this->brew($input, $formula);

        return new JsonResponse($payload, $status, $headers);
    }

    /**
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model  $collection
     * @param  array{formulas_folder_path: string, ingredients: array<int, class-string>}  $configuration
     * @param  array<int|string, mixed>|null  $formula
     *
     * @throws UnbrewableInputException
     */
    private function initContext(
        ECollection|SCollection|Model $collection,
        array $configuration,
        ?array $formula = null,
    ): BrewingContext {
        $examined = Druid::examine($collection);

        if ($examined === null) {
            throw UnbrewableInputException::nonModelItems();
        }

        [$sample, $handler] = $examined;

        [$attributes, $activeFormula] = Druid::extract(
            $sample,
            $configuration['ingredients'],
            $configuration['respect_hidden'] ?? true,
        );

        return new BrewingContext(
            raw: $collection,
            ingredients: $configuration['ingredients'],
            attributes: $attributes,
            formula: FormulaParser::normalise($formula ?? $activeFormula),
            handler: $handler,
            sample: $sample,
        );
    }
}
