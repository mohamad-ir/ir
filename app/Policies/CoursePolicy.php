<?php

namespace App\Policies;

class CoursePolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'courses';
    }
}
