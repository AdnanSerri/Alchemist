<?php

namespace Serri\Alchemist\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int|string, mixed> brew(\Illuminate\Support\Collection<int|string, mixed>|\Illuminate\Database\Eloquent\Collection<int, Model>|Model|null $collection, array<int|string, mixed>|null $formula = null)
 * @method static array<int, array<int|string, mixed>> brewMixed(\Illuminate\Support\Collection<int|string, mixed>|\Illuminate\Database\Eloquent\Collection<int, Model> $collection, array<class-string, array<int|string, mixed>> $formulaMap = [])
 * @method static AbstractPaginator<int|string, mixed>|AbstractCursorPaginator<int|string, mixed> brewBatch(AbstractPaginator<int|string, mixed>|AbstractCursorPaginator<int|string, mixed> $paginator, array<int|string, mixed>|null $formula = null)
 * @method static JsonResponse response(\Illuminate\Support\Collection<int|string, mixed>|\Illuminate\Database\Eloquent\Collection<int, Model>|Model|AbstractPaginator<int|string, mixed>|AbstractCursorPaginator<int|string, mixed>|null $input, array<int|string, mixed>|null $formula = null, int $status = 200, array<string, string> $headers = [])
 */
class Alchemist extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'Alchemist';
    }
}
