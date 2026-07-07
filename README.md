# 🧙🏻‍♂️ Laravel Alchemist ⚗️

[![Latest Version](https://img.shields.io/packagist/v/serri/alchemist.svg?style=flat-square)](https://packagist.org/packages/serri/alchemist)
[![Tests](https://img.shields.io/github/actions/workflow/status/AdnanSerri/Alchemist/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/AdnanSerri/Alchemist/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/serri/alchemist.svg?style=flat-square)](https://packagist.org/packages/serri/alchemist)
[![Total Downloads](https://img.shields.io/packagist/dt/serri/alchemist.svg?style=flat-square)](https://packagist.org/packages/serri/alchemist)

### The JSON Revolution for Laravel, a simple, fast, and elegant alternative to Laravel JSON Resource.

---

## 📖 Table of Contents

1. [Philosophy](#philosophy)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Fundamentals](#fundamentals)
5. [Quick Start](#quick-start)
6. [Usage & Examples](#usage-examples)
7. [Custom Ingredients](#custom-ingredients)
8. [Error Handling](#error-handling)
9. [Testing](#testing)
10. [Changelog](#changelog)
11. [License](#license)

---

## <a id="philosophy"></a> 🔮 Philosophy - The Problem with Traditional Laravel Resources

We've all been there:

- Creating endless resource classes that mostly repeat the same boilerplate.
- Duplicating code across multiple API responses.
- Drowning in maintenance when frontend requirements change.
- Wrestling with nested relationships that bloat your codebase.

The breaking point comes when:

- Your API evolves and resources multiply.
- Frontend devs request constant field changes.
- Your models grow, but your resource classes don't scale.
- Nested relationships turn into unmaintainable spaghetti.

The Solution: Laravel Alchemist - Formula Approach

**One File to Rule Them All**

Each model gets a single `SomeModelFormula.php` where you:

✅ Define all fields as simple strings in arrays.<br>
✅ Manage every API variation in one place.<br>
✅ Update database changes with a single edit.

**Relationship Handling Made Simple**

- Reference nested resources by their name only.
- Each relation maintains its own `Formula::class`.
- No more recursive resource nightmares.

**Frontend-Friendly Flexibility**

- Instantly modify fields without resource class hopping.
- Track all API variations through clear formula methods.
- Never miss a field update again.

**Why This Works**

- **Less Code:** Eliminates 80%+ of resource boilerplate.
- **True Maintainability:** All changes flow through controlled formulas.
- **Team Friendly:** Frontend can request changes without breaking your flow.

> *“Laravel Resources grant you the illusion of control – meticulous yet maddening. Laravel Alchemist surrenders this
false dominion... and in its place conjures true magic.„*

---

## <a id="requirements"></a> 📋 Requirements

| Laravel | PHP   | Alchemist |
|---------|-------|-----------|
| 12.x    | ≥ 8.2 | ≥ 1.1     |
| 13.x    | ≥ 8.3 | ≥ 1.1     |

> Laravel 11 is supported by Alchemist 1.0.x only: it reached end of security support in March 2026 and is no longer maintained.

---

## <a id="installation"></a> 🔧 Installation

You may install Alchemist using the Composer package manager:

```shell
composer require serri/alchemist
```

You can publish the Alchemist configuration file `config/alchemist.php` and the default `Formulas/Formula.php` using
the `vendor:publish` Artisan command:

```shell
php artisan vendor:publish --provider="Serri\Alchemist\Providers\AlchemistServiceProvider"
```

Or for the configuration file only:

```shell
php artisan vendor:publish --tag=alchemist-config
```

For the default formula class only:

```shell
php artisan vendor:publish --tag=alchemist-formula
```

---

## <a id="fundamentals"></a> 📖 Fundamentals

To wield this package's magic effectively, you must understand these arcane principles:

### **The Formulas Directory**

- Your **sacred workshop** where all model formulas reside
- Created automatically at `app/Formulas/Formula.php` when you publish the default formula class as we did in
  the [Installation](#installation) section:

```php
namespace App\Formulas;

class Formula
{
    const BlankParchment = ['id']; # Default formula.
}
```

### Crafting Your Formulas

Summon a formula class with the generator:

```shell
php artisan make:formula PostFormula

# Or scan a model: pre-fills BlankParchment with its exposed fields and
# lists its #[Mutagen] / #[Relation] methods as hints.
php artisan make:formula PostFormula --model=Post
```

The generator writes to your configured `formulas_folder_path` (default `app/Formulas/`), creates the base
`Formula` class if it is missing, and refuses to overwrite an existing formula unless you pass `--force`.

### Linting Your Formulas

Formulas are strings resolved at runtime — so let CI catch the typos before production does:

```shell
php artisan formula:lint
```

Every constant of every formula class is validated against its model using the exact same discovery the brew
pipeline uses, **including nested specs** (validated against the related model, no database needed). Unknown
fields fail with a "did you mean" suggestion; the command exits non-zero, so add it next to your test step in CI.
Models are matched by convention (`PostFormula` → `App\Models\Post`) or explicitly:

```php
class WeirdlyNamedFormula extends Formula
{
    protected static string $model = \App\Models\Post::class;
}
```

Use `--json` for machine-readable output.

Or craft one by hand in `app/Formulas/` like so:

```php
namespace App\Formulas;

class UserFormula extends Formula
{
    # Define your transformations here.
    # ex:

    const UserLogin = ['id', 'username', /*...etc.*/];

    // ... other formulas.
}
```

> #### Key Laws:
> - #### Each model deserves its own formula class `ModelNameFormula.php` <br>
> - #### The `BlankParchment` remains your fallback option.

### Using the package's default Formula

If you did not publish `app/Formulas/Formula.php`, you can still extend the default `Formula` provided by the package
like this:

```php
namespace App\Formulas;

use Serri\Alchemist\Formulas\Formula;

class UserFormula extends Formula
{
    // Define your transformations here.
}
```

---

## <a id="quick-start"></a> 🪄 Quick Start

### 1. Model Configuration

To enable formula support, models must use the `HasAlchemyFormulas` concern.

```php
use Serri\Alchemist\Concerns\HasAlchemyFormulas;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasAlchemyFormulas;

    //
}
```

### 2. Exposing Fields

By default, everything included in the `$fillable` array and the `$guarded` array is automatically available to formulas.

```php
use Serri\Alchemist\Concerns\HasAlchemyFormulas;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasAlchemyFormulas;

    # Automatically exposed to formulas.
    protected $guarded = ['id'];

    # Automatically exposed to formulas.
    protected $fillable = [
        'title',
        'description',
        'published_at',
    ];
}
```

> **Note:** Eloquent's default `$guarded = ['*']` wildcard is never treated as a field. If you rely on the default,
> expose your columns through `$fillable`.

### 3. Exposing Relationships

Relationships must be explicitly marked with the `#[Relation]` decorator to be available in formulas:

```php
use Serri\Alchemist\Decorators\Relation;

#[Relation] # Exposed to formulas as 'comments'
public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}

#[Relation(name: 'author_profile')] # Exposed to formulas as 'author_profile'
public function profile(): HasOne
{
    return $this->hasOne(Profile::class);
}
```

### 4. Exposing Custom Methods

Model methods require the `#[Mutagen]` decorator to be accessible in formulas:

```php
use Serri\Alchemist\Decorators\Mutagen;

#[Mutagen] # Exposed to formulas as 'fullName'
public function fullName(): string
{
    return "{$this->first_name} {$this->last_name}";
}

#[Mutagen(name: 'is_verified')] # Exposed to formulas as 'is_verified'
public function isVerified(): bool
{
    return $this->email_verified_at !== null;
}
```

> #### Keynotes
> - #### `$fillable` / `$guarded` fields are available in formulas by default.
> - #### **Decorators:** Only `#[Relation]` and `#[Mutagen]` methods are exposed to formulas.
> - #### The `name` argument works positionally too: `#[Mutagen('is_verified')]`.

### 5. Crafting Formulas

Once your models are properly configured, you can define formulas to transform your data. Formulas are defined in
classes within the `app/Formulas/` directory.

Here is an example:

```php
namespace App\Formulas;

class PostFormula extends Formula
{
    const Author = ['id', 'title', 'author_profile'];

    const WithComments = ['id', 'title', 'comments'];

    const Detailed = ['id', 'title', 'description', 'comments', 'author_profile'];
}
```

For the profile formula:

```php
namespace App\Formulas;

class ProfileFormula extends Formula
{
    const OnlyName = ['fullName'];

    const AnyOther = ['id', 'username', 'fullName'];
}
```

---

## <a id="usage-examples"></a> 🛠️ Usage & Examples

### Basic Data Transformation

Pass a formula straight into the brew — no global state, no sequencing:

```php
use App\Models\Post;
use App\Formulas\PostFormula;
use Serri\Alchemist\Facades\Alchemist;

// Eager-load the relations your formula uses!
$posts = Post::with('author.profile')->get();

$transformedData = Alchemist::brew($posts, PostFormula::Author);
```

...where the formula pins the whole response shape, nested relations included:

```php
class PostFormula extends Formula
{
    const Author = [
        'id',
        'title',
        'author_profile' => ProfileFormula::OnlyName, # Nested spec: shapes the relation inline.
    ];
}
```

Nested specs recurse to any depth, and any relation listed as a plain string keeps resolving through the
related model's own formula.

The classic stateful style remains fully supported — set formulas up front, brew later:

```php
Post::setFormula(PostFormula::Author);
Profile::setFormula(ProfileFormula::OnlyName);

$transformedData = Alchemist::brew($posts);
```

A per-call formula always wins over `setFormula()` for that call, so the two styles mix safely.

Results:

```php
[
  [
    'id' => 1,
    'title' => "Post 1",
    'author_profile' => [
      'fullName' => "some author name"
    ]
  ],
  [
    'id' => 2,
    'title' => 'Post 2',
    'author_profile' => [
      'fullName' => "some author name"
    ]
  ],
  [
    'id' => 3,
    'title' => 'Post 3',
    'author_profile' => [
      'fullName' => "some author name"
    ]
  ]
]
```

> **Eager loading:** brewing a `#[Relation]` field accesses the relation on each model. Always eager-load
> (`Post::with('comments')`) the relations your formula references, or you will trigger N+1 queries.

### Key Methods

| Method           | Purpose                                                | Example                                            |
|------------------|--------------------------------------------------------|----------------------------------------------------|
| `brew()`         | Transforms a model or collection into an array         | `Alchemist::brew($posts, PostFormula::Author)`     |
| `brewBatch()`    | Transforms a paginator's items, keeping its metadata   | `Alchemist::brewBatch($paginator, $formula)`       |
| `response()`     | Brews anything straight into a `JsonResponse`          | `Alchemist::response($paginator, $formula)`        |
| `setFormula()`   | Assigns the model's active (fallback) formula          | `Post::setFormula(PostFormula::DetailedView)`      |
| `unsetFormula()` | Clears it, falling back to `BlankParchment`            | `Post::unsetFormula()`                             |

The `$formula` argument is optional everywhere: omit it and the model's active formula (or `BlankParchment`) applies.

> **Laravel Octane:** formulas set via `setFormula()` are flushed automatically at every request boundary, so
> state can never leak between requests in long-lived workers. Per-call formulas never touch shared state at all.

### Patterns

#### 1. Context-Aware Formulas

```php
$formula = auth()->user()->isAdmin()
    ? PostFormula::AdminView
    : PostFormula::PublicView;

Post::setFormula($formula);
```

#### 2. Direct Model Transformation

```php
$post = Post::find(1);

return Alchemist::brew($post); // Auto-detects single model
```

#### 3. Pagination Support

```php
$paginated = Post::paginate(15);

return Alchemist::brewBatch($paginated); // Preserves pagination structure
```

`brewBatch()` accepts length-aware, simple (`simplePaginate`), and cursor (`cursorPaginate`) paginators alike.

#### 4. One-Line Controllers

`response()` brews anything — model, collection, or paginator — straight into a `JsonResponse`:

```php
public function index()
{
    return Alchemist::response(
        Post::with('comments')->paginate(15),
        PostFormula::WithComments,
    );
}

// Optional status and headers:
return Alchemist::response($post, PostFormula::Detailed, 201, ['X-Custom' => 'header']);
```

Paginators keep their pagination envelope (`data`, `total`, `links`, ...); everything else returns the brewed array.

### Syntax Variations

#### 1. Helper Function (Simplest)

```php
$posts = Post::all();
$transformed = alchemist()->brew($posts);
```

#### 2. Facade (For static contexts)

```php
use Serri\Alchemist\Facades\Alchemist;

$data = Alchemist::brew($models);
```

#### 3. Dependency Injection (Recommended for controllers)

```php
use Serri\Alchemist\Services\Alchemist;

class PostController
{
    public function __construct(
        protected Alchemist $alchemist,
    ) {}

    public function index()
    {
        return $this->alchemist->brew(Post::all());
    }
}
```

All three resolve the same container singleton.

---

## <a id="custom-ingredients"></a> 🧪 Custom Ingredients

Ingredients are the strategies the Alchemist uses to resolve each formula field. The built-ins cover `$fillable`,
`$guarded`, `#[Mutagen]`, and `#[Relation]` — but you can brew your own.

An ingredient implements `Serri\Alchemist\Contracts\IngredientContract`:

```php
namespace App\Ingredients;

use Serri\Alchemist\Contracts\IngredientContract;

final class UppercaseIngredient implements IngredientContract
{
    /**
     * The model property that lists the fields this ingredient resolves.
     */
    public static function ingredientName(): string
    {
        return 'shoutable';
    }

    /**
     * Resolve one field on one model.
     */
    public static function infuse(string $ingredient, mixed $brewing): array
    {
        return [
            $ingredient => strtoupper($brewing[$ingredient]),
        ];
    }
}
```

Register it in `config/alchemist.php`:

```php
'ingredients' => [
    \Serri\Alchemist\Ingredients\FillableIngredient::class,
    \Serri\Alchemist\Ingredients\GuardedIngredient::class,
    \Serri\Alchemist\Ingredients\MutagenIngredient::class,
    \Serri\Alchemist\Ingredients\RelationIngredient::class,

    \App\Ingredients\UppercaseIngredient::class,
],
```

Then list the fields on your model:

```php
class Post extends Model
{
    use HasAlchemyFormulas;

    protected array $shoutable = ['title'];
}
```

Rules of the craft:

- **Order matters:** when two ingredients expose the same field name, the one registered **later** wins.
- Models without the ingredient's property simply contribute no fields for it — no error.
- Decorator-driven ingredients (like the built-in Mutagen/Relation) additionally implement a static
  `usesDecorator(): bool` returning `true`, and `ingredientName()` returns the attribute class to scan for.

---

## <a id="error-handling"></a> 🚨 Error Handling

Everything the Alchemist throws extends `Serri\Alchemist\Exceptions\AlchemistException`, so one catch covers all:

| Exception                       | Thrown when                                                                  |
|---------------------------------|------------------------------------------------------------------------------|
| `UnknownFormulaFieldException`  | A formula references a field the model does not expose (typo, or forgotten decorator). |
| `UnbrewableInputException`      | The input collection contains non-models, or the model lacks the `HasAlchemyFormulas` trait. |
| `InvalidConfigurationException` | The `alchemist` config is missing or malformed.                              |
| `InvalidIngredientException`    | A configured ingredient class does not exist or lacks `infuse()`.            |

---

## <a id="testing"></a> 🧬 Testing

```shell
composer test       # PHPUnit (unit + feature suites)
composer lint       # Pint code style check
composer analyse    # PHPStan level 6
```

The CI matrix runs the suite on PHP 8.2–8.4 across Laravel 12 and 13.

---

## <a id="changelog"></a> 📆 Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history and upgrade notes.

---

## <a id="license"></a> 📜 License

This project is open-source and available under the **MIT License**.
