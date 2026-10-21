<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Sections';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-queue-list';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')->required()->maxLength(200),
            Textarea::make('description')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn ($query) => $query->withCount('lessons'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->weight('medium'),
                TextColumn::make('lessons_count')->label('Lessons')->alignEnd(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add section')->icon('heroicon-m-plus')
                    ->mutateDataUsing(fn (array $data) => [...$data, 'sort_order' => (int) $this->getOwnerRecord()->sections()->max('sort_order') + 1]),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()->modalDescription('Lessons in this section are kept and moved to “no section”.')])
            ->emptyStateHeading('No sections')
            ->emptyStateDescription('Sections group lessons into modules, e.g. “Foundations”, “Fluid”, “Putting it together”.');
    }
}
