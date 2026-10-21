<?php

namespace App\Policies;

class WebinarPolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'webinars';
    }
}
