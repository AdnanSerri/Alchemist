<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Support\Carbon;
use Serri\Alchemist\Facades\Alchemist as AlchemistFacade;
use Serri\Alchemist\Services\Alchemist;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class BrewTest extends TestCase
{
    public function test_brewing_null_returns_an_empty_array(): void
    {
        $this->assertSame([], alchemist()->brew(null));
    }

    public function test_brewing_an_empty_collection_returns_an_empty_array(): void
    {
        $this->assertSame([], alchemist()->brew(new ECollection));
        $this->assertSame([], alchemist()->brew(collect()));
    }

    public function test_brewing_a_single_model(): void
    {
        $post = $this->createPost(['title' => 'Alchemy 101']);

        Post::setFormula(PostFormula::Summary);

        $this->assertSame(
            ['id' => $post->id, 'title' => 'Alchemy 101'],
            alchemist()->brew($post)
        );
    }

    public function test_brewing_an_eloquent_collection_preserves_order(): void
    {
        $first = $this->createPost(['title' => 'First']);
        $second = $this->createPost(['title' => 'Second']);

        Post::setFormula(PostFormula::Summary);

        $this->assertSame(
            [
                ['id' => $first->id, 'title' => 'First'],
                ['id' => $second->id, 'title' => 'Second'],
            ],
            alchemist()->brew(Post::orderBy('id')->get())
        );
    }

    public function test_brewing_a_support_collection_of_models(): void
    {
        $post = $this->createPost(['title' => 'Support']);

        Post::setFormula(PostFormula::Summary);

        $this->assertSame(
            [['id' => $post->id, 'title' => 'Support']],
            alchemist()->brew(collect([$post]))
        );
    }

    public function test_brewing_uses_the_fallback_formula_when_none_is_set(): void
    {
        $post = $this->createPost(['title' => 'Fallback']);

        // PostFormula::BlankParchment is ['id', 'title'].
        $this->assertSame(
            ['id' => $post->id, 'title' => 'Fallback'],
            alchemist()->brew($post)
        );
    }

    public function test_output_keys_follow_formula_order(): void
    {
        $post = $this->createPost();

        Post::setFormula(['title', 'id']);

        $this->assertSame(['title', 'id'], array_keys(alchemist()->brew($post)));
    }

    public function test_cast_attributes_keep_their_cast_values(): void
    {
        $post = $this->createPost(['published_at' => '2026-01-15 10:00:00']);

        Post::setFormula(PostFormula::Detailed);

        $brewed = alchemist()->brew($post->fresh());

        $this->assertInstanceOf(Carbon::class, $brewed['published_at']);
        $this->assertTrue($brewed['published_at']->eq('2026-01-15 10:00:00'));
    }

    public function test_the_facade_brews(): void
    {
        $post = $this->createPost(['title' => 'Via Facade']);

        Post::setFormula(PostFormula::Summary);

        $this->assertSame(
            ['id' => $post->id, 'title' => 'Via Facade'],
            AlchemistFacade::brew($post)
        );
    }

    public function test_the_container_binding_is_a_singleton(): void
    {
        $this->assertInstanceOf(Alchemist::class, $this->app->make('Alchemist'));
        $this->assertSame($this->app->make('Alchemist'), $this->app->make('Alchemist'));
    }

    public function test_the_helper_returns_a_service_instance(): void
    {
        $this->assertInstanceOf(Alchemist::class, alchemist());
    }
}
