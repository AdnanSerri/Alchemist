<?php

namespace Serri\Alchemist\Ingredients;

use ReflectionClass;
use Serri\Alchemist\Contracts\IngredientContract;
use Serri\Alchemist\Decorators\Mutagen;
use Serri\Alchemist\Helpers\DecoratorHelper;

final class MutagenIngredient implements IngredientContract
{
    public static function usesDecorator(): bool
    {
        return true;
    }

    public static function ingredientName(): string
    {
        return Mutagen::class;
    }

    public static function infuse(string $ingredient, mixed $brewing): array
    {
        $methodName = DecoratorHelper::getMethodNameByDecorator(self::ingredientName(), $brewing, $ingredient);

        // Invoked through reflection so non-public mutagen methods keep working.
        return [
            $ingredient => (new ReflectionClass($brewing))->getMethod($methodName)->invoke($brewing),
        ];
    }
}
