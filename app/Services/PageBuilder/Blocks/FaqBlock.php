<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/** Accordion of questions; also emits FAQPage structured data for Google. */
class FaqBlock extends PageBlock
{
    public static function key(): string { return 'faq'; }

    public static function label(): string { return 'FAQ'; }

    public static function icon(): string { return 'heroicon-o-question-mark-circle'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->default('Frequently asked questions')->maxLength(160),
            Textarea::make('intro')->rows(2),
            Repeater::make('items')->schema([
                TextInput::make('question')->required()->maxLength(250),
                Textarea::make('answer')->required()->rows(3),
            ])->reorderable()->collapsible()->cloneable()->itemLabel(fn (array $state) => $state['question'] ?? null)->addActionLabel('Add question'),
        ];
    }

    public static function plainText(array $data): string
    {
        return parent::plainText($data).' '.collect($data['items'] ?? [])->map(fn ($i) => ($i['question'] ?? '').' '.($i['answer'] ?? ''))->implode(' ');
    }
}
