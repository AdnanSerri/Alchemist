<?php

namespace Serri\Alchemist\Tests\Feature;

use Illuminate\Support\Facades\File;
use Serri\Alchemist\Tests\Fixtures\Models\Cauldron;
use Serri\Alchemist\Tests\Fixtures\Models\Post;
use Serri\Alchemist\Tests\TestCase;

class MakeFormulaCommandTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alchemist_formulas_test';

        File::deleteDirectory($this->folder);

        config(['alchemist.formulas_folder_path' => $this->folder]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    public function test_it_generates_a_formula_class(): void
    {
        $this->artisan('make:formula', ['name' => 'Potion'])->assertSuccessful();

        $path = $this->folder.DIRECTORY_SEPARATOR.'PotionFormula.php';

        $this->assertFileExists($path);

        $content = File::get($path);
        $this->assertStringContainsString('namespace App\Formulas;', $content);
        $this->assertStringContainsString('class PotionFormula extends Formula', $content);
        $this->assertStringContainsString("'id',", $content);
    }

    public function test_the_formula_suffix_is_not_duplicated(): void
    {
        $this->artisan('make:formula', ['name' => 'PotionFormula'])->assertSuccessful();

        $this->assertFileExists($this->folder.DIRECTORY_SEPARATOR.'PotionFormula.php');
        $this->assertFileDoesNotExist($this->folder.DIRECTORY_SEPARATOR.'PotionFormulaFormula.php');
    }

    public function test_it_refuses_to_overwrite_without_force(): void
    {
        $this->artisan('make:formula', ['name' => 'Potion'])->assertSuccessful();

        $path = $this->folder.DIRECTORY_SEPARATOR.'PotionFormula.php';
        File::put($path, 'custom content');

        $this->artisan('make:formula', ['name' => 'Potion'])->assertFailed();
        $this->assertSame('custom content', File::get($path));

        $this->artisan('make:formula', ['name' => 'Potion', '--force' => true])->assertSuccessful();
        $this->assertStringContainsString('class PotionFormula', File::get($path));
    }

    public function test_model_scanning_prefills_exposed_fields_and_hints(): void
    {
        $this->artisan('make:formula', [
            'name' => 'ScannedPost',
            '--model' => Post::class,
        ])->assertSuccessful();

        $content = File::get($this->folder.DIRECTORY_SEPARATOR.'ScannedPostFormula.php');

        // Fillable + guarded fields land in BlankParchment.
        $this->assertStringContainsString("'id',", $content);
        $this->assertStringContainsString("'title',", $content);
        $this->assertStringContainsString("'description',", $content);
        $this->assertStringContainsString("'published_at',", $content);

        // Decorated methods become hints, not fields.
        $this->assertStringContainsString("# - 'comments' (#[Relation])", $content);
        $this->assertStringContainsString("# - 'writer' (#[Relation])", $content);
        $this->assertStringNotContainsString("'comments',", $content);
    }

    public function test_an_unknown_model_fails_cleanly(): void
    {
        $this->artisan('make:formula', [
            'name' => 'Ghost',
            '--model' => 'App\\Models\\DoesNotExist',
        ])->assertFailed();

        $this->assertFileDoesNotExist($this->folder.DIRECTORY_SEPARATOR.'GhostFormula.php');
    }

    public function test_a_model_without_the_trait_fails_cleanly(): void
    {
        $this->artisan('make:formula', [
            'name' => 'Broken',
            '--model' => Cauldron::class,
        ])->assertFailed();

        $this->assertFileDoesNotExist($this->folder.DIRECTORY_SEPARATOR.'BrokenFormula.php');
    }
}
