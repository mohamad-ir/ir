<?php

namespace App\Filament\Resources\Redirects;

use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static string|\UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make()->columns(2)->schema([
            TextInput::make('source_path')->label('Old address')->required()->prefix(url('/'))->placeholder('/old-page')
                ->unique(ignoreRecord: true)->helperText('Path only, e.g. /cases/old-name. Query strings are ignored.'),
            TextInput::make('target_url')->label('Redirect to')->required()->placeholder('/cases/new-name or https://…'),
            Select::make('status_code')->label('Type')->options([301 => '301 — moved permanently (recommended)', 302 => '302 — temporary', 410 => '410 — content removed'])->default(301)->required(),
            Toggle::make('is_active')->label('Active')->default(true)->inline(false),
            TextInput::make('notes')->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')->columns([
            TextColumn::make('source_path')->label('From')->searchable()->fontFamily('mono')->size('sm'),
            TextColumn::make('target_url')->label('To')->searchable()->fontFamily('mono')->size('sm')->limit(50),
            TextColumn::make('status_code')->label('Type')->badge()->color(fn ($state) => $state == 410 ? 'danger' : ($state == 302 ? 'warning' : 'success')),
            TextColumn::make('hits')->numeric()->sortable(),
            TextColumn::make('last_hit_at')->label('Last used')->since()->placeholder('Never'),
            IconColumn::make('is_automatic')->label('Auto')->boolean()->tooltip('Created automatically when a URL changed'),
            IconColumn::make('is_active')->label('Active')->boolean(),
        ])->filters([TernaryFilter::make('is_automatic')->label('Automatic'), TernaryFilter::make('is_active')])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
