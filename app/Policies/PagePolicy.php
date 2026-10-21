<?php

namespace App\Policies;

class PagePolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'pages';
    }
}
