<?php

namespace Serri\Alchemist\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Deliberately does NOT use HasAlchemyFormulas, to prove brewing it fails
 * with a helpful exception instead of a fatal error.
 */
class Cauldron extends Model
{
    protected $fillable = ['material'];
}
