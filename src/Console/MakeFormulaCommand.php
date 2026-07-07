<?php

namespace Serri\Alchemist\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Serri\Alchemist\Support\Druid;

class MakeFormulaCommand extends Command
{
    protected $signature = 'make:formula
        {name : The formula class name (e.g. PostFormula or Post)}
        {--model= : Model class to scan for exposed fields (short name or FQCN)}
        {--force : Overwrite the formula if it already exists}';

    protected $description = 'Create a new Alchemist formula class';

    public function handle(): int
    {
        $class = Str::finish(Str::studly($this->argument('name')), 'Formula');
        $folder = config('alchemist.formulas_folder_path');
        $path = rtrim($folder, '/\\').DIRECTORY_SEPARATOR.$class.'.php';

        if (File::exists($path) && ! $this->option('force')) {
            $this->components->error("Formula [$class] already exists at [$path]. Use --force to overwrite.");

            return self::FAILURE;
        }

        $discovered = $this->discoverFields();

        if ($discovered === null) {
            return self::FAILURE;
        }

        [$fields, $hints] = $discovered;

        File::ensureDirectoryExists($folder);

        $this->ensureBaseFormulaExists($folder);

        File::put($path, $this->render($class, $fields, $hints));

        $this->components->info("Formula [$path] created successfully.");

        return self::SUCCESS;
    }

    /**
     * Discover the fields the formula should start with. Without --model the
     * scaffold gets the bare ['id']; with it, the model's actually exposed
     * attribute fields, plus hints for its decorated methods.
     *
     * @return array{0: array<int, string>, 1: array<string, string>}|null
     */
    protected function discoverFields(): ?array
    {
        $model = $this->option('model');

        if ($model === null) {
            return [['id'], []];
        }

        $modelClass = $this->resolveModelClass($model);

        if ($modelClass === null) {
            $this->components->error("Model [$model] could not be found.");

            return null;
        }

        if (! method_exists($modelClass, 'formula')) {
            $this->components->error(
                "Model [$modelClass] does not use the HasAlchemyFormulas trait, so it cannot be scanned."
            );

            return null;
        }

        [$attributes] = Druid::extract(
            new $modelClass,
            config('alchemist.ingredients'),
            config('alchemist.respect_hidden', true),
        );

        $fields = [];
        $hints = [];

        foreach ($attributes as $field => $ingredient) {
            if (method_exists($ingredient, 'usesDecorator') && $ingredient::usesDecorator()) {
                $hints[$field] = class_basename($ingredient::ingredientName());
            } else {
                $fields[] = $field;
            }
        }

        return [$fields, $hints];
    }

    /**
     * @return class-string<Model>|null
     */
    protected function resolveModelClass(string $model): ?string
    {
        $candidates = [$model, 'App\\Models\\'.Str::studly($model)];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The namespace formulas are generated into: the first configured entry.
     */
    protected function formulaNamespace(): string
    {
        return config('alchemist.formula_namespaces', ['App\\Formulas'])[0] ?? 'App\\Formulas';
    }

    /**
     * The generated class extends {namespace}\Formula; make sure that base
     * exists, generating it when it does not.
     */
    protected function ensureBaseFormulaExists(string $folder): void
    {
        $namespace = $this->formulaNamespace();
        $basePath = rtrim($folder, '/\\').DIRECTORY_SEPARATOR.'Formula.php';

        if (File::exists($basePath) || class_exists($namespace.'\\Formula')) {
            return;
        }

        File::put($basePath, <<<PHP
        <?php

        namespace $namespace;

        class Formula
        {
            const BlankParchment = ['id']; # Default formula.
        }

        PHP);

        $this->components->info("Base formula [$basePath] created.");
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<string, string>  $hints
     */
    protected function render(string $class, array $fields, array $hints): string
    {
        $fieldLines = implode("\n", array_map(
            fn (string $field) => "        '$field',",
            $fields
        ));

        $hintLines = '';

        if ($hints !== []) {
            $hintLines = "\n    # Decorated methods available in formulas:\n";

            foreach ($hints as $field => $decorator) {
                $hintLines .= "    # - '$field' (#[$decorator])\n";
            }
        }

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ fields }}', "{{ hints }}\n"],
            [$this->formulaNamespace(), $class, $fieldLines, $hintLines],
            File::get(__DIR__.'/../../stubs/formula-class.stub')
        );
    }
}
