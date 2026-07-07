<?php

namespace Serri\Alchemist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serri\Alchemist\Support\FormulaRegistry;
use Serri\Alchemist\Tests\Fixtures\Models\Comment;
use Serri\Alchemist\Tests\Fixtures\Models\Post;

class FormulaRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        FormulaRegistry::flush();

        parent::tearDown();
    }

    public function test_set_and_get_round_trip(): void
    {
        FormulaRegistry::set(Post::class, ['id', 'title']);

        $this->assertSame(['id', 'title'], FormulaRegistry::get(Post::class));
    }

    public function test_get_returns_null_when_nothing_is_set(): void
    {
        $this->assertNull(FormulaRegistry::get(Post::class));
    }

    public function test_formulas_are_isolated_per_model_class(): void
    {
        FormulaRegistry::set(Post::class, ['title']);
        FormulaRegistry::set(Comment::class, ['body']);

        $this->assertSame(['title'], FormulaRegistry::get(Post::class));
        $this->assertSame(['body'], FormulaRegistry::get(Comment::class));
    }

    public function test_forget_clears_one_model_only(): void
    {
        FormulaRegistry::set(Post::class, ['title']);
        FormulaRegistry::set(Comment::class, ['body']);

        FormulaRegistry::forget(Post::class);

        $this->assertNull(FormulaRegistry::get(Post::class));
        $this->assertSame(['body'], FormulaRegistry::get(Comment::class));
    }

    public function test_flush_clears_everything(): void
    {
        FormulaRegistry::set(Post::class, ['title']);
        FormulaRegistry::set(Comment::class, ['body']);

        FormulaRegistry::flush();

        $this->assertNull(FormulaRegistry::get(Post::class));
        $this->assertNull(FormulaRegistry::get(Comment::class));
    }
}
