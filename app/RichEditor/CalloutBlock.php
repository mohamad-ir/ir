<?php

namespace App\RichEditor;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/** Highlighted box: clinical pearl, pitfall, key point, exam tip. */
class CalloutBlock extends RichContentCustomBlock
{
    public const TONES = [
        'pearl' => 'Clinical pearl',
        'pitfall' => 'Pitfall / warning',
        'key' => 'Key point',
        'exam' => 'Exam tip',
        'note' => 'Note',
    ];

    public static function getId(): string
    {
        return 'callout';
    }

    public static function getLabel(): string
    {
        return 'Callout (pearl, pitfall, exam tip)';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-light-bulb';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('A highlighted box that draws attention to a teaching point.')
            ->schema([
                Select::make('tone')->options(self::TONES)->default('pearl')->required(),
                TextInput::make('title')->placeholder('Defaults to the callout type'),
                Textarea::make('text')->rows(4)->required(),
            ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return (self::TONES[$config['tone'] ?? 'note'] ?? 'Callout').': '.str($config['text'] ?? '')->limit(60);
    }

    public static function toHtml(array $config, array $data): string
    {
        $tone = array_key_exists($config['tone'] ?? '', self::TONES) ? $config['tone'] : 'note';
        $title = e($config['title'] ?: self::TONES[$tone]);
        $text = nl2br(e($config['text'] ?? ''));

        return "<aside class=\"callout callout-{$tone}\" data-tone=\"{$tone}\"><p class=\"callout-title\">{$title}</p><p>{$text}</p></aside>";
    }
}
