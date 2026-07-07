<?php

namespace Serri\Alchemist\Ingredients;

use Closure;
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
        $mutagenInteraction = self::getMutagenPropertyClosure($brewing, $ingredient);

        return [
            $ingredient => $mutagenInteraction(),
        ];
    }

    private static function getMutagenPropertyClosure(mixed $brewing, string $mutagen): Closure
    {
        $mutagensMethodName = DecoratorHelper::getMethodNameByDecorator(self::ingredientName(), $brewing, $mutagen);

        return (new ReflectionClass($brewing))
            ->getMethod($mutagensMethodName)
            ->getClosure($brewing);
    }
}
