<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationLabel = 'Activity log';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('j M Y H:i')->sortable()->description(fn (ActivityLog $record) => $record->created_at?->diffForHumans()),
                TextColumn::make('user.name')->label('Who')->placeholder('System'),
                TextColumn::make('event')->badge()->color(fn ($state) => match ($state) { 'deleted', 'demo_removed' => 'danger', 'created' => 'success', 'login' => 'gray', default => 'info' }),
                TextColumn::make('description')->wrap()->searchable(),
                TextColumn::make('changes')->getStateUsing(fn (ActivityLog $record) => isset($record->properties['changed']) ? implode(', ', array_slice($record->properties['changed'], 0, 6)) : null)->color('gray')->size('xs')->toggleable(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')->label('User')->options(fn () => User::role(User::STAFF_ROLES)->pluck('name', 'id')),
                SelectFilter::make('event')->options(fn () => ActivityLog::query()->distinct()->pluck('event', 'event')),
                Filter::make('date')->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListActivityLogs::route('/')];
    }
}
