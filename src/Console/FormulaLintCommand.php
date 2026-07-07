<?php

namespace Serri\Alchemist\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Serri\Alchemist\Decorators\Relation;
use Serri\Alchemist\Exceptions\InvalidFormulaException;
use Serri\Alchemist\Helpers\DecoratorHelper;
use Serri\Alchemist\Ingredients\RelationIngredient;
use Serri\Alchemist\Support\Druid;
use Serri\Alchemist\Support\FormulaParser;
use Throwable;

class FormulaLintCommand extends Command
{
    protected $signature = 'formula:lint
        {--json : Output machine-readable JSON}';

    protected $description = 'Validate every formula constant against its model';

    /**
     * @var array<int, array{formula: string, constant: string, message: string}>
     */
    private array $problems = [];

    /**
     * @var array<int, array{formula: string, reason: string}>
     */
    private array $skipped = [];

    private int $checkedConstants = 0;

    public function handle(): int
    {
        foreach ($this->discoverFormulaClasses() as $class) {
            $this->lintClass($class);
        }

        return $this->report();
    }

    /**
     * @return array<int, class-string>
     */
    protected function discoverFormulaClasses(): array
    {
        $folder = config('alchemist.formulas_folder_path');

        if (! File::isDirectory($folder)) {
            $this->skipped[] = ['formula' => $folder, 'reason' => 'formulas folder does not exist'];

            return [];
        }

        $classes = [];

        foreach (File::files($folder) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->resolveClassFromFile($file->getPathname());

            if ($class === null) {
                $this->skipped[] = ['formula' => $file->getFilename(), 'reason' => 'could not resolve a class from the file'];

                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * @return class-string|null
     */
    protected function resolveClassFromFile(string $path): ?string
    {
        $contents = File::get($path);

        if (! preg_match('/^namespace\s+([^;]+);/m', $contents, $namespace)
            || ! preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $contents, $class)) {
            return null;
        }

        $fqcn = trim($namespace[1]).'\\'.$class[1];

        if (! class_exists($fqcn)) {
            require_once $path;
        }

        return class_exists($fqcn) ? $fqcn : null;
    }

    /**
     * @param  class-string  $class
     */
    protected function lintClass(string $class): void
    {
        $reflection = new ReflectionClass($class);

        // Constants declared on this class only — inherited BlankParchment
        // belongs to (and is linted with) its declaring class.
        $constants = array_filter(
            $reflection->getReflectionConstants(),
            fn ($constant) => $constant->getDeclaringClass()->getName() === $class
        );

        if ($constants === []) {
            return;
        }

        $model = $this->resolveModel($reflection);

        if ($model === null) {
            $this->skipped[] = ['formula' => $class, 'reason' => 'no matching model found (declare protected static string $model to map one)'];

            return;
        }

        if (! method_exists($model, 'formula')) {
            $this->skipped[] = ['formula' => $class, 'reason' => "model [$model] does not use HasAlchemyFormulas"];

            return;
        }

        foreach ($constants as $constant) {
            $this->checkedConstants++;
            $name = "$class::{$constant->getName()}";
            $value = $constant->getValue();

            if (! is_array($value)) {
                $this->problems[] = ['formula' => $class, 'constant' => $name, 'message' => 'formula constants must be arrays'];

                continue;
            }

            $this->lintFormula($name, $value, $model);
        }
    }

    /**
     * @param  array<int|string, mixed>  $formula
     * @param  class-string<Model>  $model
     */
    protected function lintFormula(string $constant, array $formula, string $model): void
    {
        try {
            $normalised = FormulaParser::normalise($formula);
        } catch (InvalidFormulaException $e) {
            $this->problems[] = ['formula' => $model, 'constant' => $constant, 'message' => $e->getMessage()];

            return;
        }

        [$attributes] = Druid::extract(
            new $model,
            config('alchemist.ingredients'),
            config('alchemist.respect_hidden', true),
        );

        foreach ($normalised as $field => $nested) {
            if (! array_key_exists($field, $attributes)) {
                $this->problems[] = [
                    'formula' => $model,
                    'constant' => $constant,
                    'message' => in_array($field, (new $model)->getHidden(), true)
                        ? "'$field' is hidden on $model (\$hidden)"
                        : "'$field' is not exposed on $model".$this->suggest($field, array_keys($attributes)),
                ];

                continue;
            }

            if ($nested === null) {
                continue;
            }

            $this->lintNestedSpec($constant, $field, $nested, $attributes[$field], $model);
        }
    }

    /**
     * @param  array<int|string, mixed>  $nested
     * @param  class-string  $ingredient
     * @param  class-string<Model>  $model
     */
    protected function lintNestedSpec(string $constant, string $field, array $nested, string $ingredient, string $model): void
    {
        if ($ingredient !== RelationIngredient::class) {
            $this->problems[] = [
                'formula' => $model,
                'constant' => $constant,
                'message' => "'$field' declares a nested formula, but its ingredient [$ingredient] cannot brew one",
            ];

            return;
        }

        try {
            $methodName = DecoratorHelper::getMethodNameByDecorator(Relation::class, new $model, $field);
            $related = get_class((new $model)->$methodName()->getRelated());
        } catch (Throwable) {
            $this->skipped[] = ['formula' => $constant, 'reason' => "could not resolve the related model behind '$field'"];

            return;
        }

        $this->lintFormula("$constant → $field", $nested, $related);
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return class-string<Model>|null
     */
    protected function resolveModel(ReflectionClass $reflection): ?string
    {
        if ($reflection->hasProperty('model')) {
            $declared = $reflection->getStaticPropertyValue('model', null);

            if (is_string($declared) && is_subclass_of($declared, Model::class)) {
                return $declared;
            }
        }

        $basename = preg_replace('/Formula$/', '', $reflection->getShortName());

        if ($basename === '') {
            return null;
        }

        foreach (['App\\Models\\'.$basename, 'App\\'.$basename] as $candidate) {
            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $known
     */
    protected function suggest(string $field, array $known): string
    {
        foreach ($known as $candidate) {
            if (levenshtein($field, $candidate) <= 2) {
                return " (did you mean '$candidate'?)";
            }
        }

        return '';
    }

    protected function report(): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode([
                'checked' => $this->checkedConstants,
                'problems' => $this->problems,
                'skipped' => $this->skipped,
            ], JSON_PRETTY_PRINT));

            return $this->problems === [] ? self::SUCCESS : self::FAILURE;
        }

        foreach ($this->problems as $problem) {
            $this->line("<fg=red>  FAIL </> {$problem['constant']}");
            $this->line("        {$problem['message']}");
            $this->newLine();
        }

        foreach ($this->skipped as $skip) {
            $this->line("<fg=yellow>  SKIP </> {$skip['formula']}: {$skip['reason']}");
        }

        if ($this->problems === []) {
            $this->components->info("All clear: {$this->checkedConstants} formula constants validated.");

            return self::SUCCESS;
        }

        $this->components->error(count($this->problems)." problems found across {$this->checkedConstants} formula constants.");

        return self::FAILURE;
    }
}
