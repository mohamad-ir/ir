<?php

namespace App\Filament\Resources\NotFoundLogs;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class NotFoundLogResource extends Resource
{
    protected static ?string $model = NotFoundLog::class;

    protected static ?string $navigationLabel = '404 monitor';

    protected static ?string $pluralModelLabel = 'Broken links (404)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('hits', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('is_ignored', false))
            ->columns([
                TextColumn::make('path')->searchable()->fontFamily('mono')->size('sm')->wrap(),
                TextColumn::make('hits')->numeric()->sortable(),
                TextColumn::make('referrer')->limit(40)->placeholder('Direct / unknown')->toggleable(),
                TextColumn::make('last_seen_at')->label('Last seen')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_ignored')->label('Ignored')->queries(true: fn ($query) => $query->withoutGlobalScopes()->where('is_ignored', true), false: fn ($query) => $query)])
            ->recordActions([
                Action::make('redirect')->label('Create redirect')->icon('heroicon-o-arrow-uturn-right')
                    ->schema(fn (NotFoundLog $record) => [
                        TextInput::make('target_url')->label('Redirect '.$record->path.' to')->required()->placeholder('/cases/…'),
                        Select::make('status_code')->options([301 => '301 — permanent', 302 => '302 — temporary', 410 => '410 — removed'])->default(301),
                    ])
                    ->action(function (NotFoundLog $record, array $data) {
                        Redirect::updateOrCreate(['source_path' => Redirect::normalizePath($record->path)], ['target_url' => $data['target_url'], 'status_code' => $data['status_code'], 'is_active' => true]);
                        $record->delete();
                        Notification::make()->success()->title('Redirect created')->send();
                    }),
                Action::make('ignore')->icon('heroicon-o-eye-slash')->iconButton()->tooltip('Ignore')->action(fn (NotFoundLog $record) => $record->update(['is_ignored' => true])),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('ignore')->label('Ignore')->icon('heroicon-o-eye-slash')->action(fn (Collection $records) => $records->each->update(['is_ignored' => true])),
                DeleteBulkAction::make()->label('Clear'),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListNotFoundLogs::route('/')];
    }
}
