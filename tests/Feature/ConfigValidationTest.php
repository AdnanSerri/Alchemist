<?php

namespace Serri\Alchemist\Tests\Feature;

use App\Formulas\PostFormula;
use Serri\Alchemist\Exceptions\InvalidConfigurationException;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class ConfigValidationTest extends TestCase
{
    public function test_brewing_fails_when_formulas_folder_path_is_missing(): void
    {
        config(['alchemist.formulas_folder_path' => null]);

        Post::setFormula(PostFormula::Summary);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('formulas_folder_path');

        alchemist()->brew($this->createPost());
    }

    public function test_brewing_fails_when_ingredients_are_missing(): void
    {
        config(['alchemist.ingredients' => null]);

        Post::setFormula(PostFormula::Summary);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('ingredients');

        alchemist()->brew($this->createPost());
    }

    public function test_the_default_config_is_merged_by_the_service_provider(): void
    {
        $this->assertNotEmpty(config('alchemist.ingredients'));
        $this->assertArrayHasKey('formulas_folder_path', config('alchemist'));
    }
}
