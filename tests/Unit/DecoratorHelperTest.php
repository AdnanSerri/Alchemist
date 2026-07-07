<?php

namespace Serri\Alchemist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serri\Alchemist\Decorators\Mutagen;
use Serri\Alchemist\Decorators\Relation;
use Serri\Alchemist\Helpers\DecoratorHelper;
use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\Fixtures\Models\Post;

class DecoratorHelperTest extends TestCase
{
    public function test_it_lists_exposed_names_of_decorated_methods(): void
    {
        $names = DecoratorHelper::getMethodsNamesByDecorator(Mutagen::class, new Author);

        $this->assertSame(['fullName', 'is_verified'], $names);
    }

    public function test_it_lists_relation_names_honouring_renames(): void
    {
        $names = DecoratorHelper::getMethodsNamesByDecorator(Relation::class, new Post);

        $this->assertSame(['comments', 'writer'], $names);
    }

    public function test_it_excludes_undecorated_methods(): void
    {
        $names = DecoratorHelper::getMethodsNamesByDecorator(Mutagen::class, new Author);

        $this->assertNotContains('secret', $names);
    }

    public function test_it_resolves_a_method_name_from_its_exposed_name(): void
    {
        $method = DecoratorHelper::getMethodNameByDecorator(Relation::class, new Post, 'writer');

        $this->assertSame('author', $method);
    }

    public function test_it_resolves_a_method_exposed_under_its_own_name(): void
    {
        $method = DecoratorHelper::getMethodNameByDecorator(Mutagen::class, new Author, 'fullName');

        $this->assertSame('fullName', $method);
    }

    public function test_it_resolves_a_renamed_mutagen(): void
    {
        $method = DecoratorHelper::getMethodNameByDecorator(Mutagen::class, new Author, 'is_verified');

        $this->assertSame('isVerified', $method);
    }
}
