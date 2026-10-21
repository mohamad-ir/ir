<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\ProfileEntry;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class PublicationsBlock extends PageBlock
{
    public static function key(): string { return 'publications'; }

    public static function label(): string { return 'Publications'; }

    public static function icon(): string { return 'heroicon-o-book-open'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->default('Selected publications')->maxLength(160),
            Textarea::make('intro')->rows(2),
            Select::make('types')->multiple()->options(['publication' => 'Journal publications', 'book' => 'Books & chapters'])->default(['publication', 'book']),
            Toggle::make('highlighted_only')->label('Only entries marked as highlighted'),
            Toggle::make('searchable')->label('Allow visitors to filter by year and type')->default(true),
            Select::make('limit')->options([0 => 'All', 5 => 5, 10 => 10, 20 => 20])->default(0)->selectablePlaceholder(false),
        ];
    }

    public static function prepare(array $data): array
    {
        $query = ProfileEntry::query()->whereIn('type', array_filter((array) ($data['types'] ?? ['publication', 'book'])))
            ->when(! empty($data['highlighted_only']), fn ($query) => $query->where('is_highlighted', true))
            ->orderByDesc('start_year')->orderBy('sort_order');
        if (! empty($data['limit'])) {
            $query->limit((int) $data['limit']);
        }
        $data['entries'] = $query->get();

        return $data;
    }
}
