<?php

namespace Serri\Alchemist\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int|string, mixed> brew(\Illuminate\Support\Collection<int|string, mixed>|\Illuminate\Database\Eloquent\Collection<int, Model>|Model|null $collection, array<int|string, mixed>|null $formula = null)
 * @method static LengthAwarePaginator<int|string, mixed> brewBatch(LengthAwarePaginator<int|string, mixed> $paginator, array<int|string, mixed>|null $formula = null)
 */
class Alchemist extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'Alchemist';
    }
}
