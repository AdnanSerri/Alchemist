<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Illuminate\Pagination\LengthAwarePaginator;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class PaginatorBrewTest extends TestCase
{
    public function test_brew_batch_transforms_items_and_preserves_pagination_metadata(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createPost(['title' => "Post $i"]);
        }

        Post::setFormula(PostFormula::Summary);

        $paginator = Post::orderBy('id')->paginate(2);
        $result = alchemist()->brewBatch($paginator);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(5, $result->total());
        $this->assertSame(2, $result->perPage());
        $this->assertSame(1, $result->currentPage());
        $this->assertSame(3, $result->lastPage());

        $items = $result->items();
        $this->assertCount(2, $items);
        $this->assertSame('Post 1', $items[0]['title']);
        $this->assertSame(['id', 'title'], array_keys($items[0]));
    }

    public function test_brew_batch_returns_an_empty_page_untouched(): void
    {
        Post::setFormula(PostFormula::Summary);

        $paginator = Post::paginate(10);
        $result = alchemist()->brewBatch($paginator);

        $this->assertSame(0, $result->total());
        $this->assertSame([], $result->items());
    }
}
