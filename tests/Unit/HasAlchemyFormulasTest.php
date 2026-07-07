<?php

namespace Serri\Alchemist\Tests\Unit;

use App\Formulas\Formula;
use App\Formulas\PostFormula;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Serri\Alchemist\Tests\Fixtures\Models\Comment;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\Fixtures\Models\Profile;

class HasAlchemyFormulasTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach ([Post::class, Comment::class, Profile::class] as $model) {
            (new ReflectionProperty($model, 'formulas'))->setValue(null, []);
        }

        parent::tearDown();
    }

    public function test_set_formula_round_trips(): void
    {
        Post::setFormula(PostFormula::Summary);

        $this->assertSame(PostFormula::Summary, Post::formula());
    }

    public function test_formulas_are_scoped_per_model_class(): void
    {
        Post::setFormula(PostFormula::Detailed);

        $this->assertNotSame(PostFormula::Detailed, Comment::formula());
    }

    public function test_it_falls_back_to_the_model_specific_blank_parchment(): void
    {
        // PostFormula defines its own BlankParchment, distinct from the generic one.
        $this->assertSame(PostFormula::BlankParchment, Post::formula());
        $this->assertSame(['id', 'title'], Post::formula());
    }

    public function test_it_falls_back_to_the_generic_formula_when_no_model_class_exists(): void
    {
        // No App\Formulas\ProfileFormula exists, so App\Formulas\Formula wins.
        $this->assertSame(Formula::BlankParchment, Profile::formula());
    }

    public function test_inherited_blank_parchment_satisfies_the_fallback(): void
    {
        // CommentFormula only inherits BlankParchment from the base Formula.
        $this->assertSame(['id'], Comment::formula());
    }
}
