<?php

namespace Serri\Alchemist\Helpers;

use ReflectionClass;
use ReflectionMethod;

class DecoratorHelper
{
    public static function getMethodNameByDecorator(string $decorator, mixed $object, string $providedName): string
    {
        $ref = new ReflectionClass($object);

        return array_values(array_filter(
            $ref->getMethods(),                                  // all methods
            function (ReflectionMethod $m) use ($decorator, $providedName) {
                if (count($m->getAttributes($decorator)) > 0) {
                    $attr = $m->getAttributes($decorator)[0];
                    if (! empty($attr->getArguments())) {
                        return $attr->getArguments()['name'] === $providedName;
                    }
                }

                return $providedName == $m->getName();
            }
        ))[0]->getName();
    }

    public static function getMethodsNamesByDecorator(string $decorator, mixed $object): array
    {
        $ref = new ReflectionClass($object);

        $methods = array_values(array_filter(
            $ref->getMethods(),
            fn (ReflectionMethod $m) => count(
                $m->getAttributes($decorator)
            ) > 0
        ));

        $methodsNames = [];

        /**
         * @var ReflectionMethod[] $methods
         * @var ReflectionMethod $method
         */
        foreach ($methods as $method) {
            $methodAttr = $method->getAttributes($decorator)[0];
            if (! empty($methodAttr->getArguments())) {
                $methodsNames[] = $methodAttr->getArguments()['name'];
            } else {
                $methodsNames[] = $method->getName();
            }
        }

        return $methodsNames;
    }
}
