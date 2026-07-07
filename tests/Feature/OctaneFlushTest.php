<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Laravel\Octane\Events\RequestReceived;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class OctaneFlushTest extends TestCase
{
    public function test_formula_state_is_flushed_at_the_octane_request_boundary(): void
    {
        // The fixture Laravel\Octane\Events\RequestReceived class makes the
        // provider register its flush listener, exactly as on a real Octane
        // worker.
        Post::setFormula(PostFormula::Detailed);
        $this->assertSame(PostFormula::Detailed, Post::formula());

        $this->app['events']->dispatch(new RequestReceived);

        // Back to the fallback: nothing leaked into the "next request".
        $this->assertSame(PostFormula::BlankParchment, Post::formula());
    }
}
