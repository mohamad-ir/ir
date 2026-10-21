<?php

namespace App\Policies;

class ClinicalCasePolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'cases';
    }
}
