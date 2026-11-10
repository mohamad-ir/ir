<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Services\PageBuilder\PageBlock;
use App\Support\SafeUrl;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class LogosBlock extends PageBlock
{
    public static function key(): string
    {
        return 'logos';
    }

    public static function label(): string
    {
        return __('Logos & affiliations');
    }

    public static function icon(): string
    {
        return 'heroicon-o-building-library';
    }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->placeholder(__('Affiliations & memberships'))->maxLength(160),
            Repeater::make('items')->schema([
                MediaPicker::make('media_id')->label(__('Logo')),
                TextInput::make('name')->required()->helperText(__('Shown as text when no logo is uploaded.')),
                TextInput::make('url')->label(__('Link'))->rule(SafeUrl::rule()),
            ])->columns(3)->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? null)->addActionLabel(__('Add logo')),
            Toggle::make('grayscale')->label(__('Show logos in greyscale'))->default(true),
        ];
    }

    public static function prepare(array $data): array
    {
        $media = Media::whereIn('id', collect($data['items'] ?? [])->pluck('media_id')->filter())->get()->keyBy('id');
        $data['items'] = collect($data['items'] ?? [])->map(fn ($i) => [...$i, 'media' => $media->get($i['media_id'] ?? 0)])->all();

        return $data;
    }
}
