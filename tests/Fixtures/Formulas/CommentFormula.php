<?php

namespace App\Formulas;

class CommentFormula extends Formula
{
    const BodyOnly = ['body'];

    const WithPost = ['id', 'body', 'post'];
}
