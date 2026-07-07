<?php

namespace Serri\Alchemist\Support;

use Illuminate\Http\Request;
use Serri\Alchemist\Exceptions\InvalidSieveRequestException;

/**
 * Builds a formula from a request's sparse-fieldset parameters, capped by an
 * allow-list formula — clients can only ever narrow the allow-list, never
 * widen it.
 *
 * Recognised query parameters:
 *
 *   fields=id,title               narrow top-level plain fields
 *   fields[self]=id,title         same, usable alongside relation fields
 *   fields[comments]=body         narrow a nested spec's plain fields
 *   fields[comments.post]=id      ...at any depth
 *   include=comments,writer       choose which nested-spec relations survive
 *   include=comments.post         dot paths choose nested branches
 *
 * With no recognised parameters the allow-list is returned verbatim, so
 * Sieve::from() is a drop-in wrapper. Plain entries are governed by
 * `fields`; entries carrying a nested spec are governed by `include`
 * (absent `include` keeps them all). Requests outside the allow-list are
 * silently dropped; Sieve::strict() throws instead.
 */
final class Sieve
{
    /**
     * @param  array<int|string, mixed>  $allowList
     * @return array<int|string, mixed>
     */
    public static function from(Request $request, array $allowList): array
    {
        return self::build($request, $allowList, strict: false);
    }

    /**
     * @param  array<int|string, mixed>  $allowList
     * @return array<int|string, mixed>
     *
     * @throws InvalidSieveRequestException
     */
    public static function strict(Request $request, array $allowList): array
    {
        return self::build($request, $allowList, strict: true);
    }

    /**
     * @param  array<int|string, mixed>  $allowList
     * @return array<int|string, mixed>
     */
    private static function build(Request $request, array $allowList, bool $strict): array
    {
        $fields = $request->query('fields');
        $include = $request->query('include');

        $topFields = null;
        $pathFields = [];

        if (is_string($fields)) {
            $topFields = self::csv($fields);
        } elseif (is_array($fields)) {
            foreach ($fields as $key => $value) {
                if (! is_string($value)) {
                    continue;
                }

                if ($key === 'self') {
                    $topFields = self::csv($value);
                } else {
                    $pathFields[$key] = self::csv($value);
                }
            }
        }

        $includeTree = is_string($include) ? self::includeTree($include) : null;

        if ($topFields === null && $includeTree === null && $pathFields === []) {
            return $allowList;
        }

        return self::sieveLevel(
            FormulaParser::normalise($allowList),
            $topFields,
            $includeTree,
            $pathFields,
            '',
            $strict,
        );
    }

    /**
     * @param  array<string, array<int|string, mixed>|null>  $allowed  normalised allow-list for this level
     * @param  array<int, string>|null  $plainRequest  requested plain fields at this level (null = all)
     * @param  array<string, mixed>|null  $includeTree  include subtree (null = include everything)
     * @param  array<string, array<int, string>>  $pathFields  fields[...] requests keyed by dot path
     * @return array<int|string, mixed>
     *
     * @throws InvalidSieveRequestException
     */
    private static function sieveLevel(
        array $allowed,
        ?array $plainRequest,
        ?array $includeTree,
        array $pathFields,
        string $prefix,
        bool $strict,
    ): array {
        $plainAllowed = array_keys(array_filter($allowed, fn ($spec) => $spec === null));
        $specAllowed = array_keys(array_filter($allowed, fn ($spec) => $spec !== null));

        if ($strict) {
            $offenders = array_merge(
                $plainRequest === null ? [] : array_diff($plainRequest, $plainAllowed),
                $includeTree === null ? [] : array_diff(array_keys($includeTree), $specAllowed),
            );

            if ($offenders !== []) {
                throw InvalidSieveRequestException::disallowed(array_values(array_map(
                    fn (string $field) => $prefix === '' ? $field : "$prefix.$field",
                    $offenders,
                )));
            }
        }

        $selectedPlains = $plainRequest === null
            ? $plainAllowed
            : array_values(array_intersect($plainRequest, $plainAllowed));

        $sieved = [];

        foreach ($allowed as $field => $spec) {
            if ($spec === null) {
                if (in_array($field, $selectedPlains, true)) {
                    $sieved[] = $field;
                }

                continue;
            }

            if ($includeTree !== null && ! array_key_exists($field, $includeTree)) {
                continue;
            }

            $path = $prefix === '' ? $field : "$prefix.$field";
            $subtree = $includeTree[$field] ?? null;

            $sieved[$field] = self::sieveLevel(
                FormulaParser::normalise($spec),
                $pathFields[$path] ?? null,
                $subtree === [] ? null : $subtree,
                $pathFields,
                $path,
                $strict,
            );
        }

        return $sieved;
    }

    /**
     * @return array<int, string>
     */
    private static function csv(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            fn (string $field) => $field !== ''
        ));
    }

    /**
     * 'comments,writer,comments.post' => ['comments' => ['post' => []], 'writer' => []]
     *
     * @return array<string, mixed>
     */
    private static function includeTree(string $value): array
    {
        $tree = [];

        foreach (self::csv($value) as $path) {
            $tree = self::graft($tree, explode('.', $path));
        }

        return $tree;
    }

    /**
     * @param  array<string, mixed>  $tree
     * @param  array<int, string>  $segments
     * @return array<string, mixed>
     */
    private static function graft(array $tree, array $segments): array
    {
        if ($segments === []) {
            return $tree;
        }

        $head = array_shift($segments);
        $branch = is_array($tree[$head] ?? null) ? $tree[$head] : [];

        $tree[$head] = self::graft($branch, $segments);

        return $tree;
    }
}
