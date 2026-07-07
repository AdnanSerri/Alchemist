<?php

namespace App\Formulas;

class PostFormula extends Formula
{
    /**
     * Model-specific default, distinct from the generic Formula::BlankParchment
     * so tests can observe which fallback tier was picked.
     */
    const BlankParchment = ['id', 'title'];

    const Summary = ['id', 'title'];

    const Detailed = ['id', 'title', 'description', 'published_at'];

    const WithComments = ['id', 'title', 'comments'];

    const WithWriter = ['id', 'title', 'writer'];

    /** Nested spec: pins the comments' formula from the parent. */
    const WithCommentBodies = ['id', 'title', 'comments' => CommentFormula::BodyOnly];
}
