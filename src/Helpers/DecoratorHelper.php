<?php

namespace Serri\Alchemist\Helpers;

use ReflectionClass;
use Serri\Alchemist\Exceptions\UnknownFormulaFieldException;

class DecoratorHelper
{
    /**
     * Exposed-name maps (exposed field name => real method name), keyed by
     * "class|decorator". Class structure is immutable at runtime, so these
     * are computed once per class per decorator and are safe to keep across
     * requests (Octane included).
     *
     * @var array<string, array<string, string>>
     */
    private static array $exposedNameMaps = [];

    /**
     * Resolve the real method name behind an exposed field name.
     *
     * @param  class-string  $decorator
     *
     * @throws UnknownFormulaFieldException
     */
    public static function getMethodNameByDecorator(string $decorator, mixed $object, string $providedName): string
    {
        return self::exposedNameMap($decorator, $object)[$providedName]
            ?? throw UnknownFormulaFieldException::forField($providedName, get_class($object));
    }

    /**
     * List the exposed names of every method carrying the decorator.
     *
     * @param  class-string  $decorator
     * @return array<int, string>
     */
    public static function getMethodsNamesByDecorator(string $decorator, mixed $object): array
    {
        return array_keys(self::exposedNameMap($decorator, $object));
    }

    public static function flushCache(): void
    {
        self::$exposedNameMaps = [];
    }

    /**
     * @param  class-string  $decorator
     * @return array<string, string>
     */
    private static function exposedNameMap(string $decorator, mixed $object): array
    {
        $key = get_class($object).'|'.$decorator;

        return self::$exposedNameMaps[$key] ??= self::buildExposedNameMap($decorator, $object);
    }

    /**
     * Scan the class once: the name a decorated method is exposed under is
     * the decorator's `name` argument (named or positional) when given,
     * otherwise the method name. First declaration wins on collisions.
     *
     * @param  class-string  $decorator
     * @return array<string, string>
     */
    private static function buildExposedNameMap(string $decorator, mixed $object): array
    {
        $map = [];

        foreach ((new ReflectionClass($object))->getMethods() as $method) {
            $attributes = $method->getAttributes($decorator);

            if ($attributes === []) {
                continue;
            }

            $exposedName = $attributes[0]->newInstance()->name ?? $method->getName();

            $map[$exposedName] ??= $method->getName();
        }

        return $map;
    }
}
