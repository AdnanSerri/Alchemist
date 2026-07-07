<?php

namespace Serri\Alchemist\Tests\Feature;

use Illuminate\Support\Facades\File;
use Serri\Alchemist\Exceptions\UnknownFormulaFieldException;
use Serri\Alchemist\Tests\Fixtures\Models\Author;
use Serri\Alchemist\Tests\TestCase;

class HiddenFieldsTest extends TestCase
{
    public function test_brewing_a_hidden_field_throws_with_a_hidden_specific_message(): void
    {
        $author = new Author(['first_name' => 'Nicolas', 'secret_token' => 'shhh']);

        $this->expectException(UnknownFormulaFieldException::class);
        $this->expectExceptionMessage("'secret_token' is hidden");
        $this->expectExceptionMessage('respect_hidden');

        alchemist()->brew($author, ['secret_token']);
    }

    public function test_non_hidden_fields_still_brew_normally(): void
    {
        $author = new Author(['first_name' => 'Nicolas', 'secret_token' => 'shhh']);

        $this->assertSame(
            ['first_name' => 'Nicolas'],
            alchemist()->brew($author, ['first_name'])
        );
    }

    public function test_disabling_respect_hidden_restores_the_old_behaviour(): void
    {
        config(['alchemist.respect_hidden' => false]);

        $author = new Author(['secret_token' => 'shhh']);

        $this->assertSame(
            ['secret_token' => 'shhh'],
            alchemist()->brew($author, ['secret_token'])
        );
    }

    public function test_the_linter_flags_hidden_fields(): void
    {
        $folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alchemist_hidden_lint';
        File::deleteDirectory($folder);
        File::ensureDirectoryExists($folder);
        config(['alchemist.formulas_folder_path' => $folder]);

        File::put($folder.DIRECTORY_SEPARATOR.'LintHiddenFormula.php', <<<'PHP'
        <?php

        namespace LintFixtures;

        class LintHiddenFormula
        {
            protected static string $model = \Serri\Alchemist\Tests\Fixtures\Models\Author::class;

            const Leaky = ['first_name', 'secret_token'];
        }
        PHP);

        $this->artisan('formula:lint')
            ->expectsOutputToContain("'secret_token' is hidden")
            ->assertFailed();

        File::deleteDirectory($folder);
    }

    public function test_make_formula_does_not_scaffold_hidden_fields(): void
    {
        $folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alchemist_hidden_make';
        File::deleteDirectory($folder);
        config(['alchemist.formulas_folder_path' => $folder]);

        $this->artisan('make:formula', [
            'name' => 'HiddenScan',
            '--model' => Author::class,
        ])->assertSuccessful();

        $content = File::get($folder.DIRECTORY_SEPARATOR.'HiddenScanFormula.php');

        $this->assertStringContainsString("'first_name',", $content);
        $this->assertStringNotContainsString('secret_token', $content);

        File::deleteDirectory($folder);
    }
}
