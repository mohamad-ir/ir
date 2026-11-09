<?php

namespace App\Filament\Resources\Popups\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\Popups\PopupResource;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPopups extends ListRecords
{
    use TranslatesPageLabels;

    protected static string $resource = PopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')->label(__('Popup settings'))->icon('heroicon-o-cog-6-tooth')->color('gray')
                ->modalHeading(__('Popup settings'))->modalWidth('lg')
                ->fillForm(fn () => [
                    'enabled' => (bool) Settings::get('popups.enabled', true),
                    'cooldown_minutes' => (int) Settings::get('popups.cooldown_minutes', 10),
                    'max_per_session' => (int) Settings::get('popups.max_per_session', 2),
                    'exclude_paths' => (string) Settings::get('popups.exclude_paths', ''),
                ])
                ->schema([
                    Toggle::make('enabled')->label(__('Show popups and announcement bars on the website'))
                        ->helperText(__('Switch off to hide all of them at once, without changing each one.')),
                    TextInput::make('max_per_session')->label(__('Automatic popups per visit (at most)'))->numeric()->minValue(0)->maxValue(20)->required()
                        ->helperText(__('Popups and slide-ins that open by themselves. 0 = no limit. Announcement bars and popups opened by a link are not counted.')),
                    TextInput::make('cooldown_minutes')->label(__('Minutes between two automatic popups'))->numeric()->minValue(0)->maxValue(1440)->required(),
                    Textarea::make('exclude_paths')->label(__('Never show on these pages'))->rows(4)->placeholder("/contact\n/courses/*/lessons/*")
                        ->helperText(__('One page address per line; * is a wildcard. Sign-in, checkout and dashboard pages are always excluded.')),
                ])
                ->action(function (array $data) {
                    Settings::setMany([
                        'popups.enabled' => (bool) $data['enabled'],
                        'popups.cooldown_minutes' => (int) $data['cooldown_minutes'],
                        'popups.max_per_session' => (int) $data['max_per_session'],
                        'popups.exclude_paths' => trim((string) $data['exclude_paths']) ?: null,
                    ]);
                    Notification::make()->success()->title(__('Saved'))->send();
                }),
            CreateAction::make()->label(__('New popup'))->icon('heroicon-m-plus'),
        ];
    }
}
