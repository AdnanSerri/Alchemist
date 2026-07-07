<?php

namespace Serri\Alchemist\Contracts;

interface IngredientContract
{
    public static function ingredientName(): string;

    public static function infuse(string $ingredient, mixed $brewing): array;
}
