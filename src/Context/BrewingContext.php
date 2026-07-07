<?php

namespace Serri\Alchemist\Context;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SCollection;
use Serri\Alchemist\Contracts\IngredientContract;

/**
 * @internal
 */
class BrewingContext
{
    /**
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model  $raw
     * @param  array<int, class-string<IngredientContract>>  $ingredients
     * @param  array<string, class-string<IngredientContract>>  $attributes
     * @param  array<int, string>  $formula
     * @param  array<int|string, mixed>  $decoction
     */
    public function __construct(
        private ECollection|SCollection|Model $raw,
        private Model $sample,
        private array $ingredients = [],
        private array $attributes = [],
        private array $formula = [],
        private string $handler = 'single',
        private array $decoction = [],
    ) {}

    /**
     * @param  ECollection<int, Model>|SCollection<int|string, mixed>|Model  $raw
     */
    public function setRaw(ECollection|SCollection|Model $raw): void
    {
        $this->raw = $raw;
    }

    /**
     * @return ECollection<int, Model>|SCollection<int|string, mixed>|Model
     */
    public function raw(): ECollection|SCollection|Model
    {
        return $this->raw;
    }

    /**
     * @param  array<int, class-string<IngredientContract>>  $ingredients
     */
    public function setIngredients(array $ingredients): void
    {
        $this->ingredients = $ingredients;
    }

    /**
     * @return array<int, class-string<IngredientContract>>
     */
    public function ingredients(): array
    {
        return $this->ingredients;
    }

    /**
     * @param  array<string, class-string<IngredientContract>>  $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array<string, class-string<IngredientContract>>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param  array<int, string>  $formula
     */
    public function setFormula(array $formula): void
    {
        $this->formula = $formula;
    }

    /**
     * @return array<int, string>
     */
    public function formula(): array
    {
        return $this->formula;
    }

    public function setHandler(string $handler): void
    {
        $this->handler = $handler;
    }

    public function handler(): string
    {
        return $this->handler;
    }

    /**
     * @param  array<int|string, mixed>  $decoction
     */
    public function setDecoction(array $decoction): void
    {
        $this->decoction = $decoction;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function decoction(): array
    {
        return $this->decoction;
    }

    public function setSample(Model $sample): void
    {
        $this->sample = $sample;
    }

    public function sample(): Model
    {
        return $this->sample;
    }
}
