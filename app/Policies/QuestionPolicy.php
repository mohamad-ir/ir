<?php

namespace App\Policies;

class QuestionPolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'questions';
    }
}
