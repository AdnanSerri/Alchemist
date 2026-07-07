<?php

namespace Serri\Alchemist\Context;

use Illuminate\Database\Eloquent\Collection as ECollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SCollection;

/**
 * @internal
 */
class BrewingContext
{
    private ECollection|SCollection|Model|null $raw;

    private array $ingredients;

    private array $attributes;

    private array $formula;

    private array $decoction;

    private string $handler;

    private ?Model $sample;

    public function __construct(
        ECollection|SCollection|Model|null $raw,
        array $ingredients = [],
        array $attributes = [],
        array $formula = [],
        string $handler = 'single',
        ?Model $sample = null,
        array $decoction = [],
    ) {
        $this->raw = $raw;
        $this->ingredients = $ingredients;
        $this->attributes = $attributes;
        $this->formula = $formula;
        $this->handler = $handler;
        $this->sample = $sample;
        $this->decoction = $decoction;
    }

    public function setRaw(ECollection|SCollection|Model|null $raw): void
    {
        $this->raw = $raw;
    }

    public function raw(): ECollection|SCollection|Model|null
    {
        return $this->raw;
    }

    public function setIngredients(array $ingredients): void
    {
        $this->ingredients = $ingredients;
    }

    public function ingredients(): array
    {
        return $this->ingredients;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function setFormula(array $formula): void
    {
        $this->formula = $formula;
    }

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

    public function setDecoction(array $decoction): void
    {
        $this->decoction = $decoction;
    }

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
