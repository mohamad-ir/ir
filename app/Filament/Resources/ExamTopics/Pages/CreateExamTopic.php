<?php

namespace App\Filament\Resources\ExamTopics\Pages;

use App\Filament\Resources\ExamTopics\ExamTopicResource;
use App\Filament\Support\ContentCreateRecord;

class CreateExamTopic extends ContentCreateRecord
{
    protected static string $resource = ExamTopicResource::class;

    protected static function previewType(): string { return 'exam_topic'; }

    protected static function permissionPrefix(): string { return 'exam_topics'; }
}
