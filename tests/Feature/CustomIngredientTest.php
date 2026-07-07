<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Serri\Alchemist\Tests\Fixtures\Ingredients\UppercaseIngredient;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class CustomIngredientTest extends TestCase
{
    public function test_a_custom_ingredient_registered_in_config_resolves_fields(): void
    {
        config([
            'alchemist.ingredients' => array_merge(
                config('alchemist.ingredients'),
                [UppercaseIngredient::class]
            ),
        ]);

        $post = $this->createPost(['title' => 'whisper']);

        Post::setFormula(PostFormula::Summary);

        // 'title' is claimed by FillableIngredient first, then overridden by
        // UppercaseIngredient — later ingredients win on name collision.
        $this->assertSame(
            ['id' => $post->id, 'title' => 'WHISPER'],
            alchemist()->brew($post)
        );
    }

    public function test_without_registration_the_custom_ingredient_is_inert(): void
    {
        $post = $this->createPost(['title' => 'whisper']);

        Post::setFormula(PostFormula::Summary);

        $this->assertSame('whisper', alchemist()->brew($post)['title']);
    }
}
