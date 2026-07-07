<?php

namespace Serri\Alchemist\Tests\Feature;

use Illuminate\Support\Facades\File;
use Serri\Alchemist\Support\FormulaRegistry;
use Serri\Alchemist\Tests\Fixtures\Models\Potion;
use Serri\Alchemist\Tests\TestCase;

class FormulaNamespacesTest extends TestCase
{
    protected function tearDown(): void
    {
        FormulaRegistry::setNamespaces(['App\\Formulas']);

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('alchemist.formula_namespaces', [
            'Modules\\Apothecary\\Formulas',
            'App\\Formulas',
        ]);
    }

    public function test_the_provider_feeds_configured_namespaces_into_the_registry(): void
    {
        $this->assertSame(
            ['Modules\\Apothecary\\Formulas', 'App\\Formulas'],
            FormulaRegistry::namespaces()
        );
    }

    public function test_fallback_resolution_searches_configured_namespaces_in_order(): void
    {
        // Modules\Apothecary\Formulas\PotionFormula exists (BlankParchment = ['name']);
        // no App\Formulas\PotionFormula does — so the module class wins.
        $this->assertSame(['name'], Potion::formula());
    }

    public function test_brewing_uses_the_module_resolved_fallback(): void
    {
        $this->assertSame(
            ['name' => 'Felix Felicis'],
            alchemist()->brew(new Potion(['name' => 'Felix Felicis']))
        );
    }

    public function test_make_formula_generates_into_the_first_configured_namespace(): void
    {
        $folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alchemist_ns_make';
        File::deleteDirectory($folder);
        config(['alchemist.formulas_folder_path' => $folder]);

        $this->artisan('make:formula', ['name' => 'Elixir'])->assertSuccessful();

        $formula = File::get($folder.DIRECTORY_SEPARATOR.'ElixirFormula.php');
        $base = File::get($folder.DIRECTORY_SEPARATOR.'Formula.php');

        $this->assertStringContainsString('namespace Modules\Apothecary\Formulas;', $formula);
        // The base is generated too, in the same namespace, because
        // Modules\Apothecary\Formulas\Formula is not autoloadable.
        $this->assertStringContainsString('namespace Modules\Apothecary\Formulas;', $base);

        File::deleteDirectory($folder);
    }
}
