<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class PerCallFormulaTest extends TestCase
{
    public function test_a_formula_can_be_passed_per_call(): void
    {
        $post = $this->createPost(['title' => 'Per Call']);

        // No setFormula() anywhere.
        $this->assertSame(
            ['id' => $post->id, 'title' => 'Per Call'],
            alchemist()->brew($post, PostFormula::Summary)
        );
    }

    public function test_the_per_call_formula_wins_over_the_active_formula(): void
    {
        $post = $this->createPost(['title' => 'Priority']);

        Post::setFormula(PostFormula::Detailed);

        $brewed = alchemist()->brew($post, ['title']);

        $this->assertSame(['title' => 'Priority'], $brewed);
    }

    public function test_the_per_call_formula_does_not_touch_global_state(): void
    {
        $post = $this->createPost();

        Post::setFormula(PostFormula::Summary);

        alchemist()->brew($post, ['title']);

        $this->assertSame(PostFormula::Summary, Post::formula());
    }

    public function test_collections_brew_with_a_per_call_formula(): void
    {
        $first = $this->createPost(['title' => 'One']);
        $second = $this->createPost(['title' => 'Two']);

        $this->assertSame(
            [['title' => 'One'], ['title' => 'Two']],
            alchemist()->brew(Post::orderBy('id')->get(), ['title'])
        );
    }

    public function test_brew_batch_accepts_a_per_call_formula(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createPost(['title' => "Post $i"]);
        }

        $paginator = alchemist()->brewBatch(Post::orderBy('id')->paginate(2), ['title']);

        $this->assertSame(3, $paginator->total());
        $this->assertSame([['title' => 'Post 1'], ['title' => 'Post 2']], $paginator->items());
    }
}
