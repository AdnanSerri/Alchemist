<?php

namespace Serri\Alchemist\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Serri\Alchemist\Helpers\DecoratorHelper;
use Serri\Alchemist\Providers\AlchemistServiceProvider;
use Serri\Alchemist\Support\Druid;
use Serri\Alchemist\Support\FormulaRegistry;
use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\Fixtures\Models\Comment;
use Serri\Alchemist\Tests\Fixtures\Models\Post;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->resetFormulas();

        // Reflection caches are class-keyed and normally harmless to share,
        // but tests that mutate instance-level $fillable must not leak.
        Druid::flushCache();
        DecoratorHelper::flushCache();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [AlchemistServiceProvider::class];
    }

    /**
     * Formulas live in static state, so anything set in one test would leak
     * into the next.
     */
    protected function resetFormulas(): void
    {
        FormulaRegistry::flush();
    }

    protected function createSchema(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('authors');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('body');
            $table->foreignId('post_id')->constrained('posts');
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('bio');
            $table->timestamps();
        });
    }

    protected function createAuthor(array $attributes = []): Author
    {
        return Author::forceCreate($attributes + [
            'first_name' => 'Adnan',
            'last_name' => 'Serri',
            'email' => 'adnan@example.com',
        ]);
    }

    protected function createPost(array $attributes = []): Post
    {
        return Post::forceCreate($attributes + [
            'title' => 'The Philosopher\'s Stone',
            'description' => 'On turning lead into gold.',
        ]);
    }

    protected function createComment(Post $post, array $attributes = []): Comment
    {
        return Comment::forceCreate($attributes + [
            'body' => 'Fascinating read.',
            'post_id' => $post->id,
        ]);
    }
}
