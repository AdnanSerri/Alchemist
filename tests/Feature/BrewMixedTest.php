<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\AuthorFormula;
use App\Formulas\PostFormula;
use Serri\Alchemist\Exceptions\UnbrewableInputException;
use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class BrewMixedTest extends TestCase
{
    public function test_a_mixed_collection_brews_with_per_class_formulas_in_order(): void
    {
        $author = $this->createAuthor(['first_name' => 'Nicolas', 'last_name' => 'Flamel']);
        $post = $this->createPost(['title' => 'Mixed']);

        $brewed = alchemist()->brewMixed(collect([$post, $author, $post]), [
            Post::class => ['title'],
            Author::class => ['fullName'],
        ]);

        $this->assertSame(
            [
                ['title' => 'Mixed'],
                ['fullName' => 'Nicolas Flamel'],
                ['title' => 'Mixed'],
            ],
            $brewed
        );
    }

    public function test_omitted_classes_fall_back_to_their_active_formula(): void
    {
        $author = $this->createAuthor();
        $post = $this->createPost(['title' => 'Fallback']);

        Author::setFormula(AuthorFormula::Named);

        $brewed = alchemist()->brewMixed(collect([$post, $author]), [
            Post::class => ['title'],
        ]);

        $this->assertSame(['title' => 'Fallback'], $brewed[0]);
        $this->assertArrayHasKey('fullName', $brewed[1]);
    }

    public function test_omitted_classes_without_an_active_formula_use_blank_parchment(): void
    {
        $post = $this->createPost(['title' => 'Parchment']);

        // PostFormula::BlankParchment is ['id', 'title'].
        $brewed = alchemist()->brewMixed(collect([$post]));

        $this->assertSame(['id' => $post->id, 'title' => 'Parchment'], $brewed[0]);
    }

    public function test_nested_specs_work_inside_the_formula_map(): void
    {
        $post = $this->createPost(['title' => 'Nested Mixed']);
        $this->createComment($post, ['body' => 'Within!']);

        $brewed = alchemist()->brewMixed(collect([$post->load('comments')]), [
            Post::class => ['title', 'comments' => ['body']],
        ]);

        $this->assertSame([['body' => 'Within!']], $brewed[0]['comments']);
    }

    public function test_an_empty_collection_brews_to_an_empty_array(): void
    {
        $this->assertSame([], alchemist()->brewMixed(collect()));
    }

    public function test_non_model_items_throw(): void
    {
        $this->expectException(UnbrewableInputException::class);

        alchemist()->brewMixed(collect([$this->createPost(), 'not a model']));
    }

    public function test_plain_brew_rejects_mixed_collections_with_a_pointer(): void
    {
        $post = $this->createPost();
        $author = $this->createAuthor();

        $this->expectException(UnbrewableInputException::class);
        $this->expectExceptionMessage('brewMixed()');

        alchemist()->brew(collect([$post, $author]), ['id']);
    }

    public function test_plain_brew_rejects_collections_with_trailing_non_models(): void
    {
        $post = $this->createPost();

        $this->expectException(UnbrewableInputException::class);

        alchemist()->brew(collect([$post, 'rogue string']), ['id']);
    }
}
