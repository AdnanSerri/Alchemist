<?php

namespace Serri\Alchemist\Tests\Feature;

use Illuminate\Http\Request;
use Serri\Alchemist\Exceptions\InvalidSieveRequestException;
use Serri\Alchemist\Support\Sieve;
use Serri\Alchemist\Tests\TestCase;

class SieveTest extends TestCase
{
    /** The allow-list ceiling used throughout: two plains + a spec'd relation tree. */
    private const ALLOW = [
        'id',
        'title',
        'comments' => ['id', 'body', 'post' => ['id', 'title']],
        'writer' => ['fullName'],
    ];

    private function request(array $query): Request
    {
        return Request::create('/', 'GET', $query);
    }

    public function test_no_parameters_returns_the_allow_list_verbatim(): void
    {
        $this->assertSame(self::ALLOW, Sieve::from($this->request([]), self::ALLOW));
    }

    public function test_fields_narrows_top_level_plain_fields(): void
    {
        $sieved = Sieve::from($this->request(['fields' => 'title']), self::ALLOW);

        $this->assertContains('title', $sieved);
        $this->assertNotContains('id', $sieved);
        // Relations are governed by `include`, which is absent — they stay.
        $this->assertArrayHasKey('comments', $sieved);
        $this->assertArrayHasKey('writer', $sieved);
    }

    public function test_unknown_requested_fields_are_silently_dropped(): void
    {
        $sieved = Sieve::from($this->request(['fields' => 'title,password,nope']), self::ALLOW);

        $this->assertContains('title', $sieved);
        $this->assertNotContains('password', $sieved);
        $this->assertNotContains('nope', $sieved);
    }

    public function test_strict_mode_throws_for_disallowed_fields(): void
    {
        $this->expectException(InvalidSieveRequestException::class);
        $this->expectExceptionMessage('password');

        Sieve::strict($this->request(['fields' => 'title,password']), self::ALLOW);
    }

    public function test_include_selects_which_relations_survive(): void
    {
        $sieved = Sieve::from($this->request(['include' => 'writer']), self::ALLOW);

        $this->assertArrayHasKey('writer', $sieved);
        $this->assertArrayNotHasKey('comments', $sieved);
        // Plain fields untouched: `fields` is absent.
        $this->assertContains('id', $sieved);
        $this->assertContains('title', $sieved);
    }

    public function test_include_dot_paths_choose_nested_branches(): void
    {
        $sieved = Sieve::from($this->request(['include' => 'comments.post']), self::ALLOW);

        $this->assertArrayHasKey('comments', $sieved);
        $this->assertArrayNotHasKey('writer', $sieved);
        $this->assertArrayHasKey('post', $sieved['comments']);
        $this->assertContains('body', $sieved['comments']);
    }

    public function test_relation_fields_narrow_the_nested_spec(): void
    {
        $sieved = Sieve::from($this->request(['fields' => ['comments' => 'body']]), self::ALLOW);

        $this->assertContains('body', $sieved['comments']);
        $this->assertNotContains('id', $sieved['comments']);
        // The nested relation spec is include-governed and include is absent.
        $this->assertArrayHasKey('post', $sieved['comments']);
    }

    public function test_deep_relation_fields_narrow_at_any_depth(): void
    {
        $sieved = Sieve::from(
            $this->request(['fields' => ['comments.post' => 'title']]),
            self::ALLOW
        );

        $this->assertContains('title', $sieved['comments']['post']);
        $this->assertNotContains('id', $sieved['comments']['post']);
    }

    public function test_fields_self_composes_with_relation_fields(): void
    {
        $sieved = Sieve::from(
            $this->request(['fields' => ['self' => 'title', 'comments' => 'body']]),
            self::ALLOW
        );

        $this->assertSame(['title'], array_values(array_filter($sieved, 'is_string')));
        $this->assertContains('body', $sieved['comments']);
    }

    public function test_strict_mode_throws_for_disallowed_includes_with_the_full_path(): void
    {
        $this->expectException(InvalidSieveRequestException::class);
        $this->expectExceptionMessage('comments.author');

        Sieve::strict($this->request(['include' => 'comments.author']), self::ALLOW);
    }

    public function test_the_sieved_formula_brews_end_to_end(): void
    {
        $post = $this->createPost(['title' => 'Sieved']);
        $this->createComment($post, ['body' => 'Through the mesh']);

        $request = $this->request(['fields' => 'title', 'include' => 'comments', 'fields' => ['self' => 'title', 'comments' => 'body']]);

        $brewed = alchemist()->brew(
            $post->load('comments'),
            Sieve::from($request, ['id', 'title', 'comments' => ['id', 'body']])
        );

        $this->assertSame(
            ['title' => 'Sieved', 'comments' => [['body' => 'Through the mesh']]],
            $brewed
        );
    }
}
