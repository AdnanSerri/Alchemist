<?php

namespace Serri\Alchemist\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Serri\Alchemist\Concerns\HasAlchemyFormulas;

/**
 * Has no App\Formulas\ProfileFormula class on purpose, so it exercises the
 * fallback to the generic App\Formulas\Formula::BlankParchment.
 */
class Profile extends Model
{
    use HasAlchemyFormulas;

    protected $guarded = ['id'];

    protected $fillable = ['bio'];
}
