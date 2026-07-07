<?php

namespace Serri\Alchemist\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Serri\Alchemist\Concerns\HasAlchemyFormulas;
use Serri\Alchemist\Decorators\Relation;

class Comment extends Model
{
    use HasAlchemyFormulas;

    protected $guarded = ['id'];

    protected $fillable = ['body'];

    #[Relation]
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
