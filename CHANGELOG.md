# Changelog

All notable changes to `serri/alchemist` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- A null to-one relation (`belongsTo` / `hasOne` / `morphOne`) now brews to `null` instead of `[]`; empty to-many relations still brew to `[]`.

## [1.5.0] - 2026-07-07

### Added

- **`Alchemist::brewMixed($collection, $formulaMap = [])`** — brews heterogeneous collections (feeds, search results, morph queries): each element uses its own class's formula, order preserved; per-class formulas can be pinned via the map.
- **`Sieve` — request-driven sparse fieldsets**: `Sieve::from($request, $allowList)` builds a formula from `fields` / `include` / `fields[relation]` query parameters, always capped by the allow-list formula (clients narrow, never widen). No parameters returns the allow-list verbatim; `Sieve::strict()` throws `InvalidSieveRequestException` for out-of-bounds requests.

### Changed

- `brew()` now fails fast with a clear message (pointing at `brewMixed()`) when given a mixed-class collection or a collection with non-model items beyond the first; previously it silently derived everything from the first model.

## [1.4.0] - 2026-07-07

### Added

- **`php artisan formula:lint`** — validates every formula constant against its model (including nested specs, resolved through the relation's related model) using the same discovery the brew pipeline uses. Unknown fields fail with a "did you mean" suggestion and a non-zero exit code for CI; `--json` for tooling; models matched by convention or via `protected static string $model`.
- **Configurable formula namespaces** (`formula_namespaces` config): fallback resolution searches the configured namespaces in order instead of hardcoding `App\Formulas`, unblocking modular / DDD codebases. `make:formula` generates into the first configured namespace (base `Formula` included).

### Changed

- **`$hidden` is now respected** (config `respect_hidden`, default `true`): fields in a model's `$hidden` property are never exposed to formulas, matching Eloquent's serialisation contract. A formula referencing a hidden field throws `UnknownFormulaFieldException` with a hidden-specific message; `formula:lint` flags it and `make:formula --model` no longer scaffolds hidden fields. Set `alchemist.respect_hidden` to `false` to restore the previous behaviour.

## [1.3.0] - 2026-07-07

### Added

- **Per-call formulas**: `brew($models, PostFormula::Author)` and `brewBatch($paginator, $formula)` accept the formula directly — no global state involved. A per-call formula wins over `setFormula()` for that call only.
- **Nested formula specs**: formula arrays may pin a relation's formula inline (`'comments' => CommentFormula::BodyOnly`), recursively. Plain string entries keep resolving through the related model's own formula, so all existing formulas remain valid.
- `Contracts\AcceptsNestedFormula` — opt-in interface for custom ingredients that can brew a field with a caller-provided nested formula.
- `Exceptions\InvalidFormulaException` — thrown for malformed formula entries and nested specs on non-relation fields.
- **`php artisan make:formula`** — generates formula classes into `formulas_folder_path` (finally giving that config key a purpose). `--model=Post` scans the model and pre-fills `BlankParchment` with its exposed fields, listing decorated methods as hints; `--force` overwrites. Creates the base `Formula` class when missing.
- **`Alchemist::response($input, $formula, $status, $headers)`** — brews a model, collection, or paginator straight into a `JsonResponse`; paginators keep their pagination envelope.
- `brewBatch()` now accepts simple (`simplePaginate`) and cursor (`cursorPaginate`) paginators in addition to length-aware ones.
- **Laravel Octane safety**: formulas set via `setFormula()` now live in a central `FormulaRegistry` that the service provider flushes at every Octane request boundary, eliminating cross-request formula leakage in long-lived workers.

## [1.2.0] - 2026-07-07

### Performance

- Decorator scans (`#[Mutagen]` / `#[Relation]` method maps) are now computed once per model class instead of once per field per row.
- The attribute map (field → ingredient) is cached per model class and ingredient list; previously nested relation brews rebuilt it for every row. The active formula is never cached.
- `Druid::flushCache()` and `DecoratorHelper::flushCache()` are available for the rare case of mutating a model's `$fillable` / `$guarded` at runtime.
- Mutagen invocation no longer constructs a bound closure per field per row.

## [1.1.0] - 2026-07-07

### Added

- Test suite (PHPUnit + Orchestra Testbench), PHPStan (level 6, clean), Pint, and a GitHub Actions CI matrix.
- Laravel 13 support.
- `Model::unsetFormula()` to clear a model's active formula and fall back to `BlankParchment`.
- Dedicated exceptions under `Serri\Alchemist\Exceptions`: `AlchemistException` (base), `InvalidConfigurationException`, `UnknownFormulaFieldException`, `UnbrewableInputException`, `InvalidIngredientException`.
- The `Alchemist` service is now bound in the container by class (`Serri\Alchemist\Services\Alchemist`), with `'Alchemist'` kept as an alias; the `alchemist()` helper and relation brewing now resolve the shared singleton.

### Fixed

- `composer.json` did not declare any `illuminate/*` dependencies.
- Decorator arguments passed positionally (`#[Mutagen('name')]`) crashed; both positional and named arguments now work.
- A method *without* a decorator could be matched by its bare name when resolving decorated methods.
- Referencing an unknown field in a formula produced a raw "Undefined array key" error; it now throws `UnknownFormulaFieldException` naming the field and model.
- Brewing a collection of non-models or a model without the `HasAlchemyFormulas` trait produced fatal errors; both now throw `UnbrewableInputException` with clear messages.
- Eloquent's default `$guarded = ['*']` leaked a literal `'*'` field into the exposed-attribute map.
- Custom ingredients whose backing property is absent on a model crashed the brew; such models now simply contribute no fields for that ingredient.
- The formulas-folder existence check in the config loader could never trigger (inverted logic) and validated a path that is never read at runtime; the check was removed, the key remains reserved for the upcoming formula generator.
- `#[Mutagen]` / `#[Relation]` declared their attribute target flag twice.
- `Alchemist::brew()` kept brewing state on the instance; state is now local to each call, making nested/re-entrant brews on the shared singleton safe.

### Changed

- **Dropped Laravel 11 support.** Laravel 11 reached end of security support in March 2026 and carries unpatched security advisories that Composer refuses to install by default. Requires Laravel 12 or 13 (PHP ≥ 8.2; Laravel 13 requires PHP ≥ 8.3).

## [1.0.3] - 2025

Last release before the changelog was introduced. See the git history for details.
