<?php

namespace Serri\Alchemist\Tests\Fixtures\Ingredients;

use Serri\Alchemist\Contracts\IngredientContract;

/**
 * Custom ingredient used to prove the config-driven extensibility point:
 * exposes fields listed in a model's $shoutable property, uppercased.
 * Registered after the built-ins, so it overrides them on name collision.
 */
final class UppercaseIngredient implements IngredientContract
{
    public static function ingredientName(): string
    {
        return 'shoutable';
    }

    public static function infuse(string $ingredient, mixed $brewing): array
    {
        return [
            $ingredient => strtoupper($brewing[$ingredient]),
        ];
    }
}
