<?php

namespace Serri\Alchemist\Contracts;

/**
 * Ingredients that can brew a field with a caller-provided nested formula
 * (e.g. 'comments' => CommentFormula::BodyOnly inside a parent formula).
 */
interface AcceptsNestedFormula
{
    /**
     * @param  array<int|string, mixed>  $formula
     * @return array<string, mixed>
     */
    public static function infuseWithFormula(string $ingredient, mixed $brewing, array $formula): array;
}
