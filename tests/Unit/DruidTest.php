<?php

namespace Serri\Alchemist\Tests\Unit;

use App\Formulas\PostFormula;
use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Support\Collection as SCollection;
use PHPUnit\Framework\TestCase;
use Serri\Alchemist\Ingredients\FillableIngredient;
use Serri\Alchemist\Ingredients\GuardedIngredient;
use Serri\Alchemist\Ingredients\MutagenIngredient;
use Serri\Alchemist\Ingredients\RelationIngredient;
use Serri\Alchemist\Support\Druid;
use Serri\Alchemist\Tests\Fixtures\Ingredients\UppercaseIngredient;
use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\Fixtures\Models\Potion;

class DruidTest extends TestCase
{
    private const INGREDIENTS = [
        FillableIngredient::class,
        GuardedIngredient::class,
        MutagenIngredient::class,
        RelationIngredient::class,
    ];

    public function test_examine_detects_a_single_model(): void
    {
        $post = new Post;

        $this->assertSame([$post, 'single'], Druid::examine($post));
    }

    public function test_examine_detects_an_eloquent_collection(): void
    {
        $first = new Post;
        $collection = new ECollection([$first, new Post]);

        $this->assertSame([$first, 'multiple'], Druid::examine($collection));
    }

    public function test_examine_detects_a_support_collection(): void
    {
        $first = new Post;
        $collection = new SCollection([$first, new Post]);

        $this->assertSame([$first, 'multiple'], Druid::examine($collection));
    }

    public function test_examine_returns_null_for_null_input(): void
    {
        $this->assertNull(Druid::examine(null));
    }

    public function test_examine_returns_null_for_an_empty_collection(): void
    {
        $this->assertNull(Druid::examine(new ECollection));
        $this->assertNull(Druid::examine(new SCollection));
    }

    public function test_examine_returns_null_for_a_collection_of_non_models(): void
    {
        $this->assertNull(Druid::examine(new SCollection([['id' => 1], ['id' => 2]])));
        $this->assertNull(Druid::examine(new SCollection(['just', 'strings'])));
    }

    public function test_extract_maps_each_exposed_field_to_its_ingredient(): void
    {
        [$attributes, $formula] = Druid::extract(new Author, self::INGREDIENTS);

        $this->assertSame(FillableIngredient::class, $attributes['first_name']);
        $this->assertSame(FillableIngredient::class, $attributes['last_name']);
        $this->assertSame(GuardedIngredient::class, $attributes['id']);
        $this->assertSame(MutagenIngredient::class, $attributes['fullName']);
        $this->assertSame(MutagenIngredient::class, $attributes['is_verified']);
        $this->assertSame(RelationIngredient::class, $attributes['posts']);
    }

    public function test_extract_uses_decorator_provided_names(): void
    {
        [$attributes] = Druid::extract(new Post, self::INGREDIENTS);

        $this->assertArrayHasKey('writer', $attributes);
        $this->assertArrayNotHasKey('author', $attributes);
    }

    public function test_extract_ignores_undecorated_methods(): void
    {
        [$attributes] = Druid::extract(new Author, self::INGREDIENTS);

        $this->assertArrayNotHasKey('secret', $attributes);
    }

    public function test_extract_returns_the_models_active_formula(): void
    {
        [, $formula] = Druid::extract(new Post, self::INGREDIENTS);

        $this->assertSame(PostFormula::BlankParchment, $formula);
    }

    public function test_extract_never_exposes_the_default_guarded_wildcard(): void
    {
        // Potion relies on Eloquent's default $guarded = ['*'].
        [$attributes] = Druid::extract(new Potion, self::INGREDIENTS);

        $this->assertArrayNotHasKey('*', $attributes);
        $this->assertSame(FillableIngredient::class, $attributes['name']);
    }

    public function test_extract_skips_ingredients_whose_property_the_model_lacks(): void
    {
        // UppercaseIngredient reads $shoutable, which only Post defines.
        $ingredients = array_merge(self::INGREDIENTS, [UppercaseIngredient::class]);

        [$attributes] = Druid::extract(new Author, $ingredients);

        $this->assertNotContains(UppercaseIngredient::class, $attributes);
    }

    public function test_the_attribute_map_cache_is_keyed_by_ingredient_list(): void
    {
        [$without] = Druid::extract(new Post, self::INGREDIENTS);
        [$with] = Druid::extract(new Post, array_merge(self::INGREDIENTS, [UppercaseIngredient::class]));

        $this->assertSame(FillableIngredient::class, $without['title']);
        $this->assertSame(UppercaseIngredient::class, $with['title']);
    }

    public function test_the_attribute_map_is_cached_per_class_until_flushed(): void
    {
        Druid::flushCache();

        [$before] = Druid::extract(new Potion, self::INGREDIENTS);
        $this->assertArrayNotHasKey('colour', $before);

        // Instance-level mutation after the first extract is intentionally
        // invisible: field exposure is cached per class, not per instance.
        $mutated = new Potion;
        $mutated->fillable(['name', 'colour']);

        [$cached] = Druid::extract($mutated, self::INGREDIENTS);
        $this->assertArrayNotHasKey('colour', $cached);

        Druid::flushCache();

        [$fresh] = Druid::extract($mutated, self::INGREDIENTS);
        $this->assertSame(FillableIngredient::class, $fresh['colour']);

        Druid::flushCache(); // don't leak the mutated map into other tests
    }

    public function test_the_formula_is_never_cached(): void
    {
        [, $before] = Druid::extract(new Post, self::INGREDIENTS);
        $this->assertSame(PostFormula::BlankParchment, $before);

        Post::setFormula(PostFormula::Detailed);

        [, $after] = Druid::extract(new Post, self::INGREDIENTS);
        $this->assertSame(PostFormula::Detailed, $after);

        Post::unsetFormula();
    }
}
