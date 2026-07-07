<?php

namespace Serri\Alchemist\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Serri\Alchemist\Concerns\HasAlchemyFormulas;
use Serri\Alchemist\Decorators\Mutagen;
use Serri\Alchemist\Decorators\Relation;

class Author extends Model
{
    use HasAlchemyFormulas;

    protected $guarded = ['id'];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
    ];

    #[Mutagen]
    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    #[Mutagen(name: 'is_verified')]
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    #[Mutagen('initials')] // positional argument on purpose
    public function makeInitials(): string
    {
        return mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1);
    }

    #[Relation]
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Deliberately undecorated: must never be exposed to formulas.
     */
    public function secret(): string
    {
        return 'never-exposed';
    }
}
