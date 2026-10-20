<?php

namespace App\Services\PageBuilder\Blocks;

use App\Enums\ProfileEntryType;
use App\Models\ProfileEntry;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

/** Lists CV entries managed under Content › Profile & CV. */
class CredentialsBlock extends PageBlock
{
    public static function key(): string { return 'credentials'; }

    public static function label(): string { return 'CV section'; }

    public static function icon(): string { return 'heroicon-o-identification'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->maxLength(160)->placeholder('e.g. Education & training'),
            Select::make('types')->label('Entry types')->multiple()->options(ProfileEntryType::options())->required()->default(['education', 'fellowship']),
            ToggleButtons::make('layout')->inline()->options(['timeline' => 'Timeline', 'cards' => 'Cards', 'list' => 'Compact list'])->default('timeline'),
            Select::make('limit')->options([0 => 'All', 3 => 3, 5 => 5, 8 => 8, 12 => 12])->default(0)->selectablePlaceholder(false),
        ];
    }

    public static function prepare(array $data): array
    {
        $types = array_filter((array) ($data['types'] ?? []));
        $query = ProfileEntry::query()->whereIn('type', $types)->ordered();
        if (! empty($data['limit'])) {
            $query->limit((int) $data['limit']);
        }
        $data['entries'] = $query->get();

        return $data;
    }
}
