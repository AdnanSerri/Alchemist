<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\CommentFormula;
use App\Formulas\PostFormula;
use Serri\Alchemist\Exceptions\InvalidFormulaException;
use Serri\Alchemist\Tests\Fixtures\Models\Comment;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class NestedFormulaTest extends TestCase
{
    public function test_a_nested_spec_shapes_the_relation(): void
    {
        $post = $this->createPost(['title' => 'Nested']);
        $this->createComment($post, ['body' => 'First!']);

        $brewed = alchemist()->brew(
            $post->load('comments'),
            ['id', 'title', 'comments' => ['body']]
        );

        $this->assertSame(
            [
                'id' => $post->id,
                'title' => 'Nested',
                'comments' => [['body' => 'First!']],
            ],
            $brewed
        );
    }

    public function test_the_nested_spec_wins_over_the_related_models_active_formula(): void
    {
        $post = $this->createPost();
        $this->createComment($post, ['body' => 'Shaped by parent']);

        Comment::setFormula(CommentFormula::WithPost); // would include id + post

        $brewed = alchemist()->brew($post->load('comments'), ['id', 'comments' => ['body']]);

        $this->assertSame([['body' => 'Shaped by parent']], $brewed['comments']);
    }

    public function test_plain_relation_entries_still_use_the_related_models_formula(): void
    {
        $post = $this->createPost();
        $comment = $this->createComment($post);

        Comment::setFormula(CommentFormula::BodyOnly);

        // 'comments' as a plain string: behaviour is exactly pre-1.3.
        $brewed = alchemist()->brew($post->load('comments'), ['id', 'comments']);

        $this->assertSame([['body' => $comment->body]], $brewed['comments']);
    }

    public function test_nested_specs_recurse(): void
    {
        $author = $this->createAuthor(['first_name' => 'Nicolas', 'last_name' => 'Flamel']);
        $post = $this->createPost(['title' => 'Deep', 'author_id' => $author->id]);
        $this->createComment($post, ['body' => 'Layer one']);

        $brewed = alchemist()->brew(
            $post->load('comments.post.author'),
            [
                'title',
                'comments' => [
                    'body',
                    'post' => ['id', 'writer' => ['fullName']],
                ],
            ]
        );

        $this->assertSame(
            [
                'title' => 'Deep',
                'comments' => [
                    [
                        'body' => 'Layer one',
                        'post' => [
                            'id' => $post->id,
                            'writer' => ['fullName' => 'Nicolas Flamel'],
                        ],
                    ],
                ],
            ],
            $brewed
        );
    }

    public function test_formula_constants_compose_nested_specs(): void
    {
        $post = $this->createPost(['title' => 'Composed']);
        $this->createComment($post, ['body' => 'Via constant']);

        // PostFormula::WithCommentBodies = ['id', 'title', 'comments' => CommentFormula::BodyOnly]
        $brewed = alchemist()->brew($post->load('comments'), PostFormula::WithCommentBodies);

        $this->assertSame([['body' => 'Via constant']], $brewed['comments']);
    }

    public function test_nested_specs_work_through_set_formula_too(): void
    {
        $post = $this->createPost();
        $this->createComment($post, ['body' => 'Static route']);

        Post::setFormula(PostFormula::WithCommentBodies);

        $brewed = alchemist()->brew($post->load('comments'));

        $this->assertSame([['body' => 'Static route']], $brewed['comments']);
    }

    public function test_a_nested_spec_on_a_non_relation_field_throws(): void
    {
        $post = $this->createPost();

        $this->expectException(InvalidFormulaException::class);
        $this->expectExceptionMessage("'title'");

        alchemist()->brew($post, ['id', 'title' => ['nope']]);
    }

    public function test_a_nested_spec_on_a_mutagen_throws(): void
    {
        $author = $this->createAuthor();

        $this->expectException(InvalidFormulaException::class);
        $this->expectExceptionMessage("'fullName'");

        alchemist()->brew($author, ['fullName' => ['nope']]);
    }
}
