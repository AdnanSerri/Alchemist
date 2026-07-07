<?php

namespace Serri\Alchemist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serri\Alchemist\Decorators\Mutagen;
use Serri\Alchemist\Decorators\Relation;
use Serri\Alchemist\Ingredients\FillableIngredient;
use Serri\Alchemist\Ingredients\GuardedIngredient;
use Serri\Alchemist\Ingredients\MutagenIngredient;
use Serri\Alchemist\Ingredients\RelationIngredient;
use Serri\Alchemist\Tests\Fixtures\Models\Author;

class IngredientsTest extends TestCase
{
    public function test_ingredient_names(): void
    {
        $this->assertSame('fillable', FillableIngredient::ingredientName());
        $this->assertSame('guarded', GuardedIngredient::ingredientName());
        $this->assertSame(Mutagen::class, MutagenIngredient::ingredientName());
        $this->assertSame(Relation::class, RelationIngredient::ingredientName());
    }

    public function test_decorator_usage_flags(): void
    {
        $this->assertTrue(MutagenIngredient::usesDecorator());
        $this->assertTrue(RelationIngredient::usesDecorator());
        $this->assertFalse(method_exists(FillableIngredient::class, 'usesDecorator'));
        $this->assertFalse(method_exists(GuardedIngredient::class, 'usesDecorator'));
    }

    public function test_fillable_infuse_reads_the_attribute(): void
    {
        $author = new Author(['first_name' => 'Adnan']);

        $this->assertSame(['first_name' => 'Adnan'], FillableIngredient::infuse('first_name', $author));
    }

    public function test_guarded_infuse_reads_the_attribute(): void
    {
        $author = new Author;
        $author->id = 7;

        $this->assertSame(['id' => 7], GuardedIngredient::infuse('id', $author));
    }

    public function test_mutagen_infuse_invokes_the_decorated_method(): void
    {
        $author = new Author(['first_name' => 'Adnan', 'last_name' => 'Serri']);

        $this->assertSame(['fullName' => 'Adnan Serri'], MutagenIngredient::infuse('fullName', $author));
    }

    public function test_mutagen_infuse_resolves_renamed_methods(): void
    {
        $author = new Author(['first_name' => 'Adnan', 'last_name' => 'Serri']);

        $this->assertSame(['is_verified' => false], MutagenIngredient::infuse('is_verified', $author));
    }
}
