<?php

namespace Serri\Alchemist\Helpers;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Serri\Alchemist\Exceptions\UnknownFormulaFieldException;

class DecoratorHelper
{
    /**
     * Resolve the real method name behind an exposed field name.
     *
     * @param  class-string  $decorator
     *
     * @throws UnknownFormulaFieldException
     */
    public static function getMethodNameByDecorator(string $decorator, mixed $object, string $providedName): string
    {
        $ref = new ReflectionClass($object);

        foreach ($ref->getMethods() as $method) {
            $attributes = $method->getAttributes($decorator);

            if ($attributes === []) {
                continue;
            }

            if (self::exposedName($attributes[0], $method) === $providedName) {
                return $method->getName();
            }
        }

        throw UnknownFormulaFieldException::forField($providedName, get_class($object));
    }

    /**
     * List the exposed names of every method carrying the decorator.
     *
     * @param  class-string  $decorator
     * @return array<int, string>
     */
    public static function getMethodsNamesByDecorator(string $decorator, mixed $object): array
    {
        $ref = new ReflectionClass($object);

        $methodsNames = [];

        foreach ($ref->getMethods() as $method) {
            $attributes = $method->getAttributes($decorator);

            if ($attributes === []) {
                continue;
            }

            $methodsNames[] = self::exposedName($attributes[0], $method);
        }

        return $methodsNames;
    }

    /**
     * The name a decorated method is exposed under: the decorator's `name`
     * argument (named or positional) when given, otherwise the method name.
     *
     * @param  ReflectionAttribute<object>  $attribute
     */
    private static function exposedName(ReflectionAttribute $attribute, ReflectionMethod $method): string
    {
        return $attribute->newInstance()->name ?? $method->getName();
    }
}
