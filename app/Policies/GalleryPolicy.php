<?php

namespace App\Policies;

class GalleryPolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'galleries';
    }
}
