<?php

namespace Serri\Alchemist\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Serri\Alchemist\Concerns\HasAlchemyFormulas;

/**
 * Deliberately relies on Eloquent's default $guarded = ['*'] so tests can
 * prove the wildcard is never treated as an exposed field.
 */
class Potion extends Model
{
    use HasAlchemyFormulas;

    protected $fillable = ['name'];
}
