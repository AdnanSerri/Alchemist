<?php

namespace Serri\Alchemist\Support;

use Serri\Alchemist\Exceptions\InvalidFormulaException;

/**
 * @internal
 */
final class FormulaParser
{
    /**
     * Normalise a formula into a uniform field => nested-formula|null map.
     *
     * Accepted entry shapes:
     *   'title'                    — plain field (integer key, string value)
     *   'comments' => [...]        — relation with a nested formula (string key, array value)
     *
     * Order is preserved; nested formulas are validated recursively when
     * their own brew runs.
     *
     * @param  array<int|string, mixed>  $formula
     * @return array<string, array<int|string, mixed>|null>
     *
     * @throws InvalidFormulaException
     */
    public static function normalise(array $formula): array
    {
        $normalised = [];

        foreach ($formula as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $normalised[$value] = null;

                continue;
            }

            if (is_string($key) && is_array($value)) {
                $normalised[$key] = $value;

                continue;
            }

            throw InvalidFormulaException::malformedEntry($key, $value);
        }

        return $normalised;
    }
}
