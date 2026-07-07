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
composer analyse                            # phpstan level 6 (52 pre-existing errors are baselined in phpstan-baseline.neon)
```

CI (`.github/workflows/tests.yml`) runs lint, PHPStan, and the test matrix (PHP 8.2–8.4 × Laravel 12/13; Laravel 13 needs PHP ≥ 8.3).

## Tests

`tests/TestCase.php` creates the SQLite schema and — critically — resets the trait's static `$formulas` on every fixture model in `tearDown()` via reflection, because the package has no reset API and formulas leak between tests otherwise. Fixture models live in `tests/Fixtures/Models/`; fixture formula classes live in `tests/Fixtures/Formulas/` but are in the **`App\Formulas` namespace** (classmap-autoloaded) because `HasAlchemyFormulas` hardcodes that namespace for fallback resolution. `Profile` deliberately has no formula class to exercise the generic fallback tier.

Known bugs (positional decorator arguments, the `'*'` default-guarded pollution, the dead folder-existence check in `BrewingConfigLoader`) are deliberately **not** covered by tests — do not add tests that lock them in; fix them and then test the fixed behaviour.

## Architecture

The package uses alchemy-themed naming throughout. Translation key: **brew** = transform, **formula** = list of fields to output, **ingredient** = strategy for resolving one kind of field, **decoction** = the output array, **Druid** = input inspector, **Mutagen** = computed-value method, **decorator** = PHP attribute.

### Transformation pipeline (`brew()`)

`Serri\Alchemist\Services\Alchemist::brew($modelOrCollection)` is the entry point:

1. `Support\BrewingConfigLoader` loads and validates the `alchemist` config on every call.
2. `Support\Druid::examine()` inspects the input — returns a sample model (the collection's first item, or the model itself) plus a handler key (`'single'` or `'multiple'`).
3. `Support\Druid::extract()` reflects over the **sample model** to build an attribute map: field name → Ingredient class. Non-decorator ingredients (fillable/guarded) read the model's protected property by the name from `ingredientName()`; decorator ingredients scan methods carrying the PHP attribute (`#[Relation]`, `#[Mutagen]`) via `Helpers\DecoratorHelper`.
4. Everything is packed into a `Context\BrewingContext` (mutable DTO carried through the pipeline).
5. `Resolvers\BrewingHandlerResolver` maps the handler key to `Handlers\SingleBrewingHandler` or `Handlers\MultipleBrewingHandler`. Both loop over the formula's field names and delegate each field to `Handlers\AttributeBrewingHandler::brew()`, which looks up the field's Ingredient class in the attribute map and calls its static `infuse()`.

`brewBatch(LengthAwarePaginator)` wraps `brew()` on the paginator's items and swaps the collection back in, preserving pagination metadata.

### Ingredients (extensibility point)

Ingredients implement `Contracts\IngredientContract` (`ingredientName()` + `infuse()`); the ordered list lives in `config/alchemist.php` under `ingredients`, and users can append custom ones. Built-ins:

- `FillableIngredient` / `GuardedIngredient` — expose model attributes listed in `$fillable` / `$guarded` (read via reflection on the protected property).
- `MutagenIngredient` — invokes model methods marked `#[Mutagen]` (optionally renamed via `#[Mutagen(name: '...')]`).
- `RelationIngredient` — for methods marked `#[Relation]`, recursively brews the related model/collection with a **new** `Alchemist` instance, using the related model's own active formula.

Ingredients that opt into attribute-scanning declare `usesDecorator(): bool` returning true (checked with `method_exists`, not part of the contract).

### Formula selection (global static state)

Models opt in with the `Concerns\HasAlchemyFormulas` trait. `Model::setFormula(PostFormula::SomeConst)` stores the formula in a **static array keyed by model class** — this is global mutable state for the request. `formula()` falls back to a `BlankParchment` constant, resolved in order: `App\Formulas\{Model}Formula` → `App\Formulas\Formula` → the package's `Formulas\Formula` (`['id']`). Nested relations therefore pick up whatever formula was set on the related model class before `brew()` was called.

### Registration

`Providers\AlchemistServiceProvider` merges config, registers the `'Alchemist'` container singleton (backing the `Facades\Alchemist` facade), and publishes two tags: `alchemist-config` (config file) and `alchemist-formula` (`stubs/formula.stub` → `app/Formulas/Formula.php`). A global `alchemist()` helper in `src/Helpers/functions.php` is autoloaded via composer `files`.

## Gotchas

- `@internal` classes (`BrewingContext`, `Druid`, handlers, resolver, config loader) are not part of the public API; the public surface is the service/facade/helper, the trait, the decorators, and `IngredientContract`.
- The attribute map is built from the collection's **first model only** — mixed-model collections are not supported.
- Releases are cut by git tag only (`v1.0.0`–`v1.0.3` so far); `composer.json` intentionally has no `version` field and `composer.lock` is gitignored.
- README documents behaviour that the code must keep matching (e.g. publish tags, `BlankParchment` fallback law). It still claims Laravel ≥ 11 and has a broken `vendor:publish --provider` path — pending README fixes are tracked in the enhancement plan (Phase 5).
