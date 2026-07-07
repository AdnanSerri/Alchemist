<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Serri\Alchemist\Facades\Alchemist;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class ResponseTest extends TestCase
{
    public function test_a_model_brews_into_a_json_response(): void
    {
        $post = $this->createPost(['title' => 'Respond']);

        $response = Alchemist::response($post, PostFormula::Summary);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['id' => $post->id, 'title' => 'Respond'],
            $response->getData(true)
        );
    }

    public function test_a_collection_brews_into_a_json_response(): void
    {
        $this->createPost(['title' => 'One']);
        $this->createPost(['title' => 'Two']);

        $response = Alchemist::response(Post::orderBy('id')->get(), ['title']);

        $this->assertSame([['title' => 'One'], ['title' => 'Two']], $response->getData(true));
    }

    public function test_a_paginator_keeps_its_envelope(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createPost(['title' => "Post $i"]);
        }

        $response = Alchemist::response(Post::orderBy('id')->paginate(2), ['title']);

        $payload = $response->getData(true);

        $this->assertSame(5, $payload['total']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame([['title' => 'Post 1'], ['title' => 'Post 2']], $payload['data']);
    }

    public function test_status_and_headers_pass_through(): void
    {
        $post = $this->createPost();

        $response = Alchemist::response($post, ['title'], 201, ['X-Brewed-By' => 'Alchemist']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Alchemist', $response->headers->get('X-Brewed-By'));
    }

    public function test_null_input_responds_with_an_empty_array(): void
    {
        $response = Alchemist::response(null);

        $this->assertSame([], $response->getData(true));
    }

    public function test_brew_batch_supports_simple_paginators(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createPost(['title' => "Post $i"]);
        }

        $paginator = alchemist()->brewBatch(Post::orderBy('id')->simplePaginate(2), ['title']);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame([['title' => 'Post 1'], ['title' => 'Post 2']], $paginator->items());
    }

    public function test_brew_batch_supports_cursor_paginators(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createPost(['title' => "Post $i"]);
        }

        $paginator = alchemist()->brewBatch(Post::orderBy('id')->cursorPaginate(2), ['title']);

        $this->assertInstanceOf(CursorPaginator::class, $paginator);
        $this->assertSame([['title' => 'Post 1'], ['title' => 'Post 2']], $paginator->items());
    }
}
