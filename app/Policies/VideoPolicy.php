<?php

namespace App\Policies;

class VideoPolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'videos';
    }
}
