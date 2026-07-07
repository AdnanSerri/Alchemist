<?php

namespace Serri\Alchemist\Contracts;

interface IngredientContract
{
    public static function ingredientName(): string;

    /**
     * @return array<string, mixed>
     */
    public static function infuse(string $ingredient, mixed $brewing): array;
}
