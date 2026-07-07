<?php

namespace Serri\Alchemist\Tests\Feature;

use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\TestCase;

class MutagenBrewTest extends TestCase
{
    public function test_it_brews_default_renamed_and_positionally_renamed_mutagens(): void
    {
        $author = $this->createAuthor([
            'first_name' => 'Nicolas',
            'last_name' => 'Flamel',
            'email_verified_at' => now(),
        ]);

        Author::setFormula(['fullName', 'is_verified', 'initials']);

        $this->assertSame(
            [
                'fullName' => 'Nicolas Flamel',
                'is_verified' => true,
                'initials' => 'NF',
            ],
            alchemist()->brew($author)
        );
    }
}
