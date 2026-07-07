<?php

namespace Serri\Alchemist\Tests\Feature;

use Illuminate\Support\Facades\File;
use Serri\Alchemist\Tests\TestCase;

class FormulaLintCommandTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alchemist_lint_test';

        File::deleteDirectory($this->folder);
        File::ensureDirectoryExists($this->folder);

        config(['alchemist.formulas_folder_path' => $this->folder]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    private function writeFormula(string $class, string $body): void
    {
        File::put($this->folder.DIRECTORY_SEPARATOR.$class.'.php', <<<PHP
        <?php

        namespace LintFixtures;

        class $class
        {
            protected static string \$model = \\Serri\\Alchemist\\Tests\\Fixtures\\Models\\Post::class;

            $body
        }
        PHP);
    }

    public function test_valid_formulas_pass(): void
    {
        $this->writeFormula('LintValidFormula', <<<'PHP'
            const Good = ['id', 'title'];
            const Nested = ['id', 'comments' => ['body'], 'writer' => ['fullName']];
        PHP);

        $this->artisan('formula:lint')
            ->expectsOutputToContain('All clear')
            ->assertSuccessful();
    }

    public function test_an_unknown_field_fails_with_a_suggestion(): void
    {
        $this->writeFormula('LintBadFieldFormula', <<<'PHP'
            const Bad = ['id', 'titel'];
        PHP);

        // Both phrases sit on one output line; expectations consume lines
        // sequentially, so assert them as a single substring.
        $this->artisan('formula:lint')
            ->expectsOutputToContain("'titel' is not exposed on Serri\Alchemist\Tests\Fixtures\Models\Post (did you mean 'title'?)")
            ->assertFailed();
    }

    public function test_nested_specs_are_validated_against_the_related_model(): void
    {
        $this->writeFormula('LintBadNestedFormula', <<<'PHP'
            const Bad = ['id', 'comments' => ['bdy']];
        PHP);

        $this->artisan('formula:lint')
            ->expectsOutputToContain("'bdy' is not exposed")
            ->assertFailed();
    }

    public function test_a_nested_spec_on_a_non_relation_field_fails(): void
    {
        $this->writeFormula('LintNestedOnFillableFormula', <<<'PHP'
            const Bad = ['id', 'title' => ['anything']];
        PHP);

        $this->artisan('formula:lint')
            ->expectsOutputToContain('cannot brew one')
            ->assertFailed();
    }

    public function test_malformed_entries_fail(): void
    {
        $this->writeFormula('LintMalformedFormula', <<<'PHP'
            const Bad = ['title' => 'oops'];
        PHP);

        $this->artisan('formula:lint')
            ->expectsOutputToContain('Malformed formula entry')
            ->assertFailed();
    }

    public function test_formulas_without_a_resolvable_model_are_skipped_not_failed(): void
    {
        File::put($this->folder.DIRECTORY_SEPARATOR.'LintMysteryFormula.php', <<<'PHP'
        <?php

        namespace LintFixtures;

        class LintMysteryFormula
        {
            const Something = ['id'];
        }
        PHP);

        $this->artisan('formula:lint')
            ->expectsOutputToContain('no matching model found')
            ->assertSuccessful();
    }

    public function test_json_output_is_machine_readable(): void
    {
        $this->writeFormula('LintJsonFormula', <<<'PHP'
            const Bad = ['nope'];
        PHP);

        $this->artisan('formula:lint', ['--json' => true])->assertFailed();
    }

    public function test_an_empty_folder_passes(): void
    {
        $this->artisan('formula:lint')
            ->expectsOutputToContain('All clear')
            ->assertSuccessful();
    }
}
