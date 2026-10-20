<?php

namespace App\Filament\Resources\ReusableBlocks;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\ReusableBlock;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReusableBlockResource extends Resource
{
    protected static ?string $model = ReusableBlock::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static ?int $navigationSort = 2;

    public static function usageCount(ReusableBlock $block): int
    {
        return Page::where('blocks', 'like', '%"reusable_block_id":'.$block->id.'%')->orWhere('blocks', 'like', '%"reusable_block_id":"'.$block->id.'"%')->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'xl' => 12])->components([
            Group::make()->columnSpan(['xl' => 5])->schema([
                Section::make()->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    Textarea::make('description')->rows(2)->helperText('Where and why this block is used.'),
                ]),
                PageResource::builder('blocks', includeReusable: false)->label('Sections'),
            ]),
            Group::make()->columnSpan(['xl' => 7])->schema([View::make('filament.page-builder.preview')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->weight('medium')->description(fn (ReusableBlock $record) => $record->description),
            TextColumn::make('sections')->getStateUsing(fn (ReusableBlock $record) => count($record->blocks ?? [])),
            TextColumn::make('used')->label('Used on pages')->getStateUsing(fn (ReusableBlock $record) => static::usageCount($record)),
            TextColumn::make('updated_at')->since(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReusableBlocks::route('/'),
            'create' => Pages\CreateReusableBlock::route('/create'),
            'edit' => Pages\EditReusableBlock::route('/{record}/edit'),
        ];
    }
}
