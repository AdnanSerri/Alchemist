<?php

namespace Serri\Alchemist\Support;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SCollection;
use ReflectionClass;
use ReflectionException;
use Serri\Alchemist\Contracts\IngredientContract;
use Serri\Alchemist\Helpers\DecoratorHelper;

/**
 * @internal
 */
final class Druid
{
    public static function examine(ECollection|SCollection|Model|null $collection): ?array
    {
        if (! $collection) {
            return null;
        }

        // Checking if the given collection is one model.

        if ($collection instanceof Model) {
            return [$collection, 'single'];
        }

        // Should check that raw has at least one Model in the array to continue.

        if ($collection->isNotEmpty()) {
            return [$collection->first(), 'multiple'];
        }

        return null;
    }

    /**
     * @throws ReflectionException
     */
    public static function extract(?Model $sample, array $ingredients): ?array
    {
        $reflection = new ReflectionClass($sample);

        $attributes = [];

        /**
         * @var IngredientContract[] $ingredients
         * @var IngredientContract $ingredient
         */
        foreach ($ingredients as $ingredient) {

            if (method_exists($ingredient, 'usesDecorator') and $ingredient::usesDecorator()) {

                $methodsNames = DecoratorHelper::getMethodsNamesByDecorator($ingredient::ingredientName(), $sample);

                $attributes = array_merge(
                    $attributes,
                    array_fill_keys($methodsNames, $ingredient)
                );

            } else {
                $attributes = array_merge(
                    $attributes,
                    array_fill_keys($reflection->getProperty($ingredient::ingredientName())->getValue($sample), $ingredient) ?? []
                );
            }
        }

        $formula = $sample::formula();

        return [$attributes, $formula];
    }
}
