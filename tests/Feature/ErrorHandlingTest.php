<?php

namespace Serri\Alchemist\Tests\Feature;

use Serri\Alchemist\Exceptions\UnbrewableInputException;
use Serri\Alchemist\Exceptions\UnknownFormulaFieldException;
use Serri\Alchemist\Tests\Fixtures\Models\Cauldron;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\Fixtures\Models\Potion;
use Serri\Alchemist\Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_an_unknown_formula_field_throws_a_helpful_exception(): void
    {
        Post::setFormula(['id', 'tittle']); // typo on purpose

        $this->expectException(UnknownFormulaFieldException::class);
        $this->expectExceptionMessage("'tittle'");
        $this->expectExceptionMessage(Post::class);

        alchemist()->brew($this->createPost());
    }

    public function test_a_collection_of_non_models_throws(): void
    {
        $this->expectException(UnbrewableInputException::class);

        alchemist()->brew(collect([['id' => 1], ['id' => 2]]));
    }

    public function test_a_model_without_the_trait_throws(): void
    {
        $this->expectException(UnbrewableInputException::class);
        $this->expectExceptionMessage(Cauldron::class);
        $this->expectExceptionMessage('HasAlchemyFormulas');

        alchemist()->brew(new Cauldron(['material' => 'iron']));
    }

    public function test_default_guarded_models_expose_fillable_fields_only(): void
    {
        // Potion has no $guarded, so Eloquent defaults it to ['*'].
        Potion::setFormula(['name']);

        $this->assertSame(
            ['name' => 'Felix Felicis'],
            alchemist()->brew(new Potion(['name' => 'Felix Felicis']))
        );
    }

    public function test_default_guarded_models_do_not_expose_unlisted_fields(): void
    {
        Potion::setFormula(['id']); // not fillable, guarded by '*'

        $this->expectException(UnknownFormulaFieldException::class);
        $this->expectExceptionMessage("'id'");

        alchemist()->brew(new Potion(['name' => 'Felix Felicis']));
    }
}
