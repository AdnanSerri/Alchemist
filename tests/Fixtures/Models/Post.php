<?php

namespace Serri\Alchemist\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Serri\Alchemist\Concerns\HasAlchemyFormulas;
use Serri\Alchemist\Decorators\Relation;

class Post extends Model
{
    use HasAlchemyFormulas;

    protected $guarded = ['id'];

    protected $fillable = [
        'title',
        'description',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Exposed only when the custom UppercaseIngredient is registered in config.
     */
    protected array $shoutable = ['title'];

    #[Relation]
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    #[Relation(name: 'writer')]
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
