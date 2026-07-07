<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\AuthorFormula;
use App\Formulas\CommentFormula;
use App\Formulas\PostFormula;
use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\Fixtures\Models\Comment;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class RelationBrewTest extends TestCase
{
    public function test_it_brews_a_has_many_relation_with_the_related_models_formula(): void
    {
        $post = $this->createPost(['title' => 'With Comments']);
        $this->createComment($post, ['body' => 'First!']);
        $this->createComment($post, ['body' => 'Second!']);

        Post::setFormula(PostFormula::WithComments);
        Comment::setFormula(CommentFormula::BodyOnly);

        $this->assertSame(
            [
                'id' => $post->id,
                'title' => 'With Comments',
                'comments' => [
                    ['body' => 'First!'],
                    ['body' => 'Second!'],
                ],
            ],
            alchemist()->brew($post->load('comments'))
        );
    }

    public function test_it_brews_a_renamed_belongs_to_relation(): void
    {
        $author = $this->createAuthor([
            'first_name' => 'Nicolas',
            'last_name' => 'Flamel',
            'email_verified_at' => now(),
        ]);
        $post = $this->createPost(['author_id' => $author->id]);

        Post::setFormula(PostFormula::WithWriter);
        Author::setFormula(AuthorFormula::Named);

        $brewed = alchemist()->brew($post->load('author'));

        $this->assertSame(
            ['fullName' => 'Nicolas Flamel', 'is_verified' => true],
            $brewed['writer']
        );
    }

    public function test_nested_relations_fall_back_to_the_related_models_default_formula(): void
    {
        $post = $this->createPost();
        $comment = $this->createComment($post);

        Post::setFormula(PostFormula::WithComments);
        // No formula set on Comment: falls back to CommentFormula's inherited ['id'].

        $brewed = alchemist()->brew($post->load('comments'));

        $this->assertSame([['id' => $comment->id]], $brewed['comments']);
    }

    public function test_an_empty_has_many_relation_brews_to_an_empty_array(): void
    {
        $post = $this->createPost();

        Post::setFormula(PostFormula::WithComments);

        $brewed = alchemist()->brew($post->load('comments'));

        $this->assertSame([], $brewed['comments']);
    }

    public function test_a_null_belongs_to_relation_brews_to_an_empty_array(): void
    {
        $post = $this->createPost(['author_id' => null]);

        Post::setFormula(PostFormula::WithWriter);

        $brewed = alchemist()->brew($post->load('author'));

        $this->assertSame([], $brewed['writer']);
    }

    public function test_relation_heavy_collections_brew_correctly(): void
    {
        Post::setFormula(PostFormula::WithComments);
        Comment::setFormula(CommentFormula::BodyOnly);

        foreach (range(1, 30) as $i) {
            $post = $this->createPost(['title' => "Post $i"]);
            $this->createComment($post, ['body' => "Comment on $i"]);
            $this->createComment($post, ['body' => "Second on $i"]);
        }

        $brewed = alchemist()->brew(Post::with('comments')->orderBy('id')->get());

        $this->assertCount(30, $brewed);
        $this->assertSame('Post 17', $brewed[16]['title']);
        $this->assertCount(2, $brewed[16]['comments']);
        $this->assertSame('Comment on 17', $brewed[16]['comments'][0]['body']);
    }

    public function test_relations_brew_within_collections(): void
    {
        $postA = $this->createPost(['title' => 'A']);
        $postB = $this->createPost(['title' => 'B']);
        $this->createComment($postA, ['body' => 'On A']);

        Post::setFormula(PostFormula::WithComments);
        Comment::setFormula(CommentFormula::BodyOnly);

        $brewed = alchemist()->brew(Post::with('comments')->orderBy('id')->get());

        $this->assertSame([['body' => 'On A']], $brewed[0]['comments']);
        $this->assertSame([], $brewed[1]['comments']);
    }
}
