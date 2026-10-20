<?php

namespace App\Policies;

class ExamTopicPolicy extends ContentPolicy
{
    protected function prefix(): string
    {
        return 'exam_topics';
    }
}
