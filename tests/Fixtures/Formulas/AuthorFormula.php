<?php

namespace App\Formulas;

class AuthorFormula extends Formula
{
    const Named = ['fullName', 'is_verified'];

    const WithPosts = ['id', 'first_name', 'posts'];
}
