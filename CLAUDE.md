# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Laravel Alchemist (`serri/alchemist`) is a Laravel package offering an alternative to Laravel JSON Resources. Instead of resource classes, users define "formulas" — plain array constants of field names on classes in `app/Formulas/` — and the package transforms models/collections into arrays at runtime via reflection.

Requires PHP ≥ 8.2, supports Laravel 12 and 13 (Laravel 11 was dropped: it is EOL and blocked by Composer's security-advisory audit). This is a library (no application code); tests run through `orchestra/testbench` against a Laravel skeleton with an in-memory SQLite database.

## Commands

```shell
composer install
composer test                               # phpunit (suites: Unit, Feature)
vendor/bin/phpunit --filter SomeTestName    # run a single test
composer lint                               # pint --test (check code style)
composer fix                                # pint (apply code style)
composer analyse                            # phpstan level 6 (clean, no baseline — keep it that way)
```

CI (`.github/workflows/tests.yml`) runs lint, PHPStan, and the test matrix (PHP 8.2–8.4 × Laravel 12/13; Laravel 13 needs PHP ≥ 8.3).

## Tests

`tests/TestCase.php` creates the SQLite schema and — critically — calls `unsetFormula()` on every fixture model in `tearDown()`, because formulas live in static state and leak between tests otherwise. Fixture models live in `tests/Fixtures/Models/`; fixture formula classes live in `tests/Fixtures/Formulas/` but are in the **`App\Formulas` namespace** (classmap-autoloaded) because `HasAlchemyFormulas` hardcodes that namespace for fallback resolution. Several fixtures exist to pin specific behaviours: `Profile` has no formula class (generic fallback tier), `Potion` keeps Eloquent's default `$guarded = ['*']` (wildcard must never become a field), `Cauldron` lacks the trait entirely (must throw `UnbrewableInputException`).

## Architecture

The package uses alchemy-themed naming throughout. Translation key: **brew** = transform, **formula** = list of fields to output, **ingredient** = strategy for resolving one kind of field, **decoction** = the output array, **Druid** = input inspector, **Mutagen** = computed-value method, **decorator** = PHP attribute.

### Transformation pipeline (`brew()`)

`Serri\Alchemist\Services\Alchemist::brew($modelOrCollection, ?array $formula = null)` is the entry point. A per-call `$formula` wins over the model's active formula for that call only.

1. `Support\BrewingConfigLoader` loads and validates the `alchemist` config on every call.
2. `Support\Druid::examine()` inspects the input — returns a sample model (the collection's first item, or the model itself) plus a handler key (`'single'` or `'multiple'`).
3. `Support\Druid::extract()` reflects over the **sample model** to build an attribute map: field name → Ingredient class. Non-decorator ingredients (fillable/guarded) read the model's protected property by the name from `ingredientName()`; decorator ingredients scan methods carrying the PHP attribute (`#[Relation]`, `#[Mutagen]`) via `Helpers\DecoratorHelper`. Both the attribute map (per class + ingredient list) and the decorator scans (per class + decorator) are **cached in static properties** — class structure is immutable at runtime, so the caches survive across brews and Octane requests. The active formula is never cached. `flushCache()` exists on both; the test `TestCase` flushes them in `tearDown()`.
4. `Support\FormulaParser::normalise()` converts the formula into a uniform `field => nested-formula|null` map. Entries are either plain field names (`'title'`) or **nested specs** (`'comments' => CommentFormula::BodyOnly`); anything else throws `InvalidFormulaException`. Everything is packed into a `Context\BrewingContext` (mutable DTO carried through the pipeline; `formula()` returns the *normalised* map).
5. `Resolvers\BrewingHandlerResolver` maps the handler key to `Handlers\SingleBrewingHandler` or `Handlers\MultipleBrewingHandler`. Both loop over the normalised formula and delegate each field (plus its nested spec, if any) to `Handlers\AttributeBrewingHandler::brew()`, which looks up the field's Ingredient class in the attribute map and calls its static `infuse()` — or, when a nested spec is present, `infuseWithFormula()` via the opt-in `Contracts\AcceptsNestedFormula` interface (only `RelationIngredient` implements it; a nested spec on any other ingredient throws).

`brewBatch($paginator, ?array $formula = null)` wraps `brew()` on the paginator's items and swaps the collection back in, preserving pagination metadata — accepts length-aware, simple, and cursor paginators. `response($input, $formula, $status, $headers)` brews anything (model / collection / paginator / null) into a `JsonResponse`.

### Ingredients (extensibility point)

Ingredients implement `Contracts\IngredientContract` (`ingredientName()` + `infuse()`); the ordered list lives in `config/alchemist.php` under `ingredients`, and users can append custom ones. Built-ins:

- `FillableIngredient` / `GuardedIngredient` — expose model attributes listed in `$fillable` / `$guarded` (read via reflection on the protected property).
- `MutagenIngredient` — invokes model methods marked `#[Mutagen]` (optionally renamed via `#[Mutagen(name: '...')]`).
- `RelationIngredient` — for methods marked `#[Relation]`, recursively brews the related model/collection via the shared `Alchemist` singleton, using the related model's own active formula.

Ingredients that opt into attribute-scanning declare `usesDecorator(): bool` returning true (checked with `method_exists`, not part of the contract).

### Formula selection

Precedence per brew: **per-call formula argument** → nested spec from the parent formula (for relations) → the model's active formula (`setFormula()`) → `BlankParchment` fallback, resolved in order: `App\Formulas\{Model}Formula` → `App\Formulas\Formula` → the package's `Formulas\Formula` (`['id']`).

Models opt in with the `Concerns\HasAlchemyFormulas` trait. `Model::setFormula()` stores state in the central `Support\FormulaRegistry` (static, keyed by model class). When Laravel Octane is present, the service provider flushes the registry on Octane's `RequestReceived` event, so formulas cannot leak between requests on long-lived workers (tested via a classmap fixture stand-in for the Octane event class in `tests/Fixtures/Octane/`).

`php artisan make:formula {name} {--model=} {--force}` (`Console\MakeFormulaCommand`) scaffolds formula classes into `formulas_folder_path`, scanning the model through the same `Druid` discovery when `--model` is given.

### Registration

`Providers\AlchemistServiceProvider` merges config, registers a singleton for `Services\Alchemist::class` with `'Alchemist'` as an alias (backing the `Facades\Alchemist` facade), and publishes two tags: `alchemist-config` (config file) and `alchemist-formula` (`stubs/formula.stub` → `app/Formulas/Formula.php`). A global `alchemist()` helper in `src/Helpers/functions.php` (autoloaded via composer `files`) resolves that singleton, as does `RelationIngredient` for nested brews — `brew()` keeps all state local to the call, so re-entering the singleton is safe.

### Errors

All failures throw subclasses of `Exceptions\AlchemistException`: `InvalidConfigurationException` (bad `alchemist` config), `UnknownFormulaFieldException` (formula references an unexposed field), `UnbrewableInputException` (non-model collection items, or model without the trait), `InvalidIngredientException` (ingredient class without `infuse()`), `InvalidFormulaException` (malformed formula entry, or nested spec on a non-relation field). Keep new failure paths inside this hierarchy.

## Gotchas

- `@internal` classes (`BrewingContext`, `Druid`, handlers, resolver, config loader) are not part of the public API; the public surface is the service/facade/helper, the trait, the decorators, and `IngredientContract`.
- The attribute map is built from the collection's **first model only** — mixed-model collections are not supported.
- Releases are cut by git tag (`v1.1.0` is the latest); `composer.json` intentionally has no `version` field and `composer.lock` is gitignored. Cut the `[Unreleased]` changelog section into a version heading as part of each release.
- README documents behaviour that the code must keep matching (publish tags, `BlankParchment` fallback law, the custom-ingredient contract, the exceptions table) — update it in the same PR as any public-API change.
