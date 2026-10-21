<?php

namespace App\Policies;

class ArticlePolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'articles';
    }
}
