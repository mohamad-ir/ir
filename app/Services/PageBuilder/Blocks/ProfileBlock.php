<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Services\PageBuilder\PageBlock;
use App\Support\Html;
use App\Support\RichText;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

/** Doctor introduction: photo, credentials, biography. Defaults come from Settings › Doctor profile. */
class ProfileBlock extends PageBlock
{
    public static function key(): string { return 'profile'; }

    public static function label(): string { return 'Doctor profile'; }

    public static function icon(): string { return 'heroicon-o-user-circle'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->default('About')->maxLength(80),
                TextInput::make('heading')->placeholder('Defaults to the doctor’s name')->maxLength(160),
                TextInput::make('credentials')->placeholder('Defaults to profile credentials'),
                TextInput::make('role')->placeholder('Defaults to profile role'),
            ]),
            MediaPicker::make('photo_id')->label('Photo')->helperText('Defaults to the profile photo in Settings.'),
            RichEditor::make('bio')->label('Biography')->toolbarButtons([['bold', 'italic', 'link'], ['bulletList'], ['h3', 'blockquote']]),
            static::buttonsRepeater(),
            Toggle::make('show_social')->label('Show professional profile links (LinkedIn, ORCID…)')->default(true),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['photo'] = Media::find($data['photo_id'] ?? null) ?? Media::find(setting('doctor.photo_id'));
        $data['heading'] = ($data['heading'] ?? null) ?: setting('doctor.name');
        $data['credentials'] = ($data['credentials'] ?? null) ?: setting('doctor.credentials');
        $data['role'] = ($data['role'] ?? null) ?: setting('doctor.role');
        $data['html'] = filled($data['bio'] ?? null) ? RichText::render($data['bio']) : e(setting('doctor.short_bio'));

        return $data;
    }

    public static function plainText(array $data): string
    {
        return trim(($data['heading'] ?? '').' '.Html::text($data['bio'] ?? ''));
    }
}
