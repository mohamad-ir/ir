<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use App\Support\Html;
use App\Support\RichText;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class RichTextBlock extends PageBlock
{
    public static function key(): string { return 'rich_text'; }

    public static function label(): string { return 'Text'; }

    public static function icon(): string { return 'heroicon-o-document-text'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->maxLength(160),
            RichEditor::make('content')->required()
                ->customBlocks(RichText::customBlocks())
                ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('editor')->fileAttachmentsVisibility('public'),
            ToggleButtons::make('align')->inline()->options(['left' => 'Left', 'center' => 'Centred'])->default('left'),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['html'] = RichText::render($data['content'] ?? '');

        return $data;
    }

    public static function plainText(array $data): string
    {
        return trim(($data['heading'] ?? '').' '.Html::text(is_string($data['content'] ?? null) ? $data['content'] : ''));
    }
}
