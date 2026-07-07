<?php

namespace Serri\Alchemist\Support;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SCollection;
use ReflectionClass;
use Serri\Alchemist\Contracts\IngredientContract;
use Serri\Alchemist\Exceptions\UnbrewableInputException;
use Serri\Alchemist\Helpers\DecoratorHelper;

/**
 * @internal
 */
final class Druid
{
    /**
     * Inspect the input and return a sample model plus the handler key,
     * or null when there is nothing brewable in it.
     *
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model|null  $collection
     * @return array{0: Model, 1: string}|null
     */
    public static function examine(ECollection|SCollection|Model|null $collection): ?array
    {
        if (! $collection) {
            return null;
        }

        if ($collection instanceof Model) {
            return [$collection, 'single'];
        }

        if ($collection->isNotEmpty() && $collection->first() instanceof Model) {
            return [$collection->first(), 'multiple'];
        }

        return null;
    }

    /**
     * Attribute maps keyed by "class|ingredient,ingredient,...". Rebuilding
     * one costs a full reflection pass, and nested relation brews would
     * otherwise pay it once per row. Field exposure is derived from class
     * structure, so the cache is safe across requests; instance-level
     * mutations of $fillable / $guarded after the first brew of a class are
     * not picked up (call flushCache() if you really do that).
     *
     * @var array<string, array<string, class-string<IngredientContract>>>
     */
    private static array $attributeMaps = [];

    /**
     * Build the attribute map (exposed field name => ingredient class) and
     * fetch the sample model's active formula. The map is cached per model
     * class and ingredient list; the formula is read fresh on every call,
     * as it changes at runtime via setFormula().
     *
     * @param  array<int, class-string<IngredientContract>>  $ingredients
     * @return array{0: array<string, class-string<IngredientContract>>, 1: array<int, string>}
     *
     * @throws UnbrewableInputException
     */
    public static function extract(Model $sample, array $ingredients): array
    {
        if (! method_exists($sample, 'formula')) {
            throw UnbrewableInputException::missingTrait(get_class($sample));
        }

        $key = get_class($sample).'|'.implode(',', $ingredients);

        $attributes = self::$attributeMaps[$key] ??= self::buildAttributeMap($sample, $ingredients);

        $formula = $sample::formula();

        return [$attributes, $formula];
    }

    public static function flushCache(): void
    {
        self::$attributeMaps = [];
    }

    /**
     * @param  array<int, class-string<IngredientContract>>  $ingredients
     * @return array<string, class-string<IngredientContract>>
     */
    private static function buildAttributeMap(Model $sample, array $ingredients): array
    {
        $reflection = new ReflectionClass($sample);

        $attributes = [];

        foreach ($ingredients as $ingredient) {
            if (method_exists($ingredient, 'usesDecorator') && $ingredient::usesDecorator()) {
                $fieldNames = DecoratorHelper::getMethodsNamesByDecorator($ingredient::ingredientName(), $sample);
            } else {
                $fieldNames = self::propertyFieldNames($reflection, $sample, $ingredient::ingredientName());
            }

            $attributes = array_merge($attributes, array_fill_keys($fieldNames, $ingredient));
        }

        return $attributes;
    }

    /**
     * Read the field names an ingredient exposes through a model property.
     * A model without the property simply contributes no fields, and the
     * '*' wildcard (Eloquent's default $guarded) is never a real field.
     *
     * @param  ReflectionClass<Model>  $reflection
     * @return array<int, string>
     */
    private static function propertyFieldNames(ReflectionClass $reflection, Model $sample, string $property): array
    {
        if (! $reflection->hasProperty($property)) {
            return [];
        }

        $fieldNames = $reflection->getProperty($property)->getValue($sample);

        if (! is_array($fieldNames)) {
            return [];
        }

        return array_values(array_filter($fieldNames, fn ($field) => $field !== '*'));
    }
}
