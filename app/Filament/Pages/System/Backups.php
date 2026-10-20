<?php

namespace App\Filament\Pages\System;

use App\Services\Backup\BackupService;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class Backups extends Page
{
    protected static ?string $slug = 'system/backups';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.backups';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('backups.manage') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')->label('Create backup now')->icon('heroicon-o-plus')
                ->schema([Toggle::make('media')->label('Include media library')->default(fn () => (bool) Settings::get('backup.include_media', true))])
                ->action(function (array $data) {
                    try {
                        $name = app(BackupService::class)->create($data['media']);
                        Notification::make()->success()->title('Backup created')->body($name)->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Backup failed')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('schedule')->label('Schedule')->icon('heroicon-o-clock')->color('gray')
                ->fillForm(fn () => ['schedule' => Settings::get('backup.schedule'), 'keep' => Settings::get('backup.keep'), 'include_media' => Settings::get('backup.include_media')])
                ->schema([
                    Select::make('schedule')->options(['off' => 'Off', 'daily' => 'Daily (03:10)', 'weekly' => 'Weekly (Sunday 03:10)'])->required(),
                    TextInput::make('keep')->label('Number of backups to keep')->numeric()->minValue(1)->maxValue(30)->required(),
                    Toggle::make('include_media')->label('Include media library in scheduled backups'),
                ])
                ->action(function (array $data) {
                    Settings::setMany(['backup.schedule' => $data['schedule'], 'backup.keep' => (int) $data['keep'], 'backup.include_media' => (bool) $data['include_media']]);
                    Notification::make()->success()->title('Backup schedule saved')->body('Scheduled backups need the cron job from the installation guide.')->send();
                }),
        ];
    }

    public function download(string $name)
    {
        abort_unless(static::canAccess(), 403);

        return response()->download(app(BackupService::class)->path($name));
    }

    public function deleteBackup(string $name): void
    {
        abort_unless(static::canAccess(), 403);
        app(BackupService::class)->delete($name);
        Notification::make()->success()->title('Backup deleted')->send();
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')->label('Restore')->color('danger')->icon('heroicon-o-arrow-uturn-left')
            ->visible(fn () => auth()->user()->isSuperAdmin())
            ->modalHeading('Restore this backup?')
            ->modalDescription('The current database will be replaced. A safety backup of the current state is created first. Visitors may see errors for a minute.')
            ->schema([
                Toggle::make('media')->label('Also restore media files')->default(true),
                TextInput::make('confirm')->label('Type RESTORE to confirm')->required()->in(['RESTORE']),
            ])
            ->action(function (array $data, array $arguments) {
                try {
                    app(BackupService::class)->restore($arguments['name'], $data['media']);
                    Notification::make()->success()->title('Restore complete')->body('You may need to sign in again.')->persistent()->send();
                } catch (Throwable $e) {
                    Notification::make()->danger()->title('Restore failed')->body($e->getMessage())->persistent()->send();
                }
            });
    }

    public function getViewData(): array
    {
        return ['backups' => app(BackupService::class)->list(), 'schedule' => Settings::get('backup.schedule'), 'zip' => class_exists(\ZipArchive::class)];
    }
}
