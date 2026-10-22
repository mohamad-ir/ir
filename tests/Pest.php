<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)->in('Unit');

require_once __DIR__.'/Feature/Commerce/helpers.php';
require_once __DIR__.'/Feature/V13/helpers.php';
