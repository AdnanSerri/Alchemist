<?php

namespace Serri\Alchemist\Ingredients;

use Serri\Alchemist\Contracts\IngredientContract;

final class FillableIngredient implements IngredientContract
{
    public static function ingredientName(): string
    {
        return 'fillable';
    }

    public static function infuse(string $ingredient, mixed $brewing): array
    {
        return [
            $ingredient => $brewing[$ingredient],
        ];
    }
}
