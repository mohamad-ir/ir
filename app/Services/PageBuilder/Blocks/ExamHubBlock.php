<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\ExamTopic;
use App\Models\Question;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ExamHubBlock extends PageBlock
{
    public static function key(): string { return 'exam_hub'; }

    public static function label(): string { return 'FRCS / FRCOphth topics'; }

    public static function icon(): string { return 'heroicon-o-clipboard-document-check'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->default('Exam preparation')->maxLength(80),
            TextInput::make('heading')->default('FRCS & FRCOphth preparation')->maxLength(160),
            Textarea::make('intro')->rows(2),
            TextInput::make('limit')->numeric()->default(6)->minValue(1)->maxValue(24),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['topics'] = ExamTopic::published()->withCount(['questions' => fn ($q) => $q->published()])->orderBy('sort_order')->limit((int) ($data['limit'] ?? 6))->get();
        $data['question_count'] = Question::published()->count();

        return $data;
    }
}
