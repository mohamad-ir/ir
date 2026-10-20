<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\ReusableBlock;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;

/** Places a saved reusable block; editing the reusable block updates every page using it. */
class ReusableBlockRef extends PageBlock
{
    public static function key(): string { return 'reusable'; }

    public static function label(): string { return 'Reusable block'; }

    public static function icon(): string { return 'heroicon-o-arrow-path-rounded-square'; }

    public static function schema(): array
    {
        return [
            Select::make('reusable_block_id')->label('Reusable block')->required()->searchable()
                ->options(fn () => ReusableBlock::orderBy('name')->pluck('name', 'id'))
                ->helperText('Edit reusable blocks under Design › Reusable blocks.'),
        ];
    }

    public static function make(): Block
    {
        return Block::make(static::key())
            ->label(fn (?array $state) => 'Reusable — '.(ReusableBlock::find($state['reusable_block_id'] ?? null)?->name ?? 'choose a block'))
            ->icon(static::icon())
            ->schema(static::schema());
    }
}
