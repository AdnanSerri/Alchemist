<?php

namespace Serri\Alchemist\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array brew(\Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|Model|null $collection)
 * @method static LengthAwarePaginator brewBatch(LengthAwarePaginator $paginator)
 */
class Alchemist extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'Alchemist';
    }
}
