<?php

namespace Serri\Alchemist\Ingredients;

use Serri\Alchemist\Contracts\AcceptsNestedFormula;
use Serri\Alchemist\Contracts\IngredientContract;
use Serri\Alchemist\Decorators\Relation;
use Serri\Alchemist\Helpers\DecoratorHelper;
use Serri\Alchemist\Services\Alchemist;

final class RelationIngredient implements AcceptsNestedFormula, IngredientContract
{
    public static function usesDecorator(): bool
    {
        return true;
    }

    public static function ingredientName(): string
    {
        return Relation::class;
    }

    public static function infuse(string $ingredient, mixed $brewing): array
    {
        $relationName = self::getRelationName($brewing, $ingredient);

        return [
            $ingredient => app(Alchemist::class)->brew($brewing->$relationName),
        ];
    }

    public static function infuseWithFormula(string $ingredient, mixed $brewing, array $formula): array
    {
        $relationName = self::getRelationName($brewing, $ingredient);

        return [
            $ingredient => app(Alchemist::class)->brew($brewing->$relationName, $formula),
        ];
    }

    private static function getRelationName(mixed $brewing, string $relation): string
    {
        return DecoratorHelper::getMethodNameByDecorator(self::ingredientName(), $brewing, $relation);
    }
}
