<?php

namespace App\Filament\Resources\Roles;

use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationLabel = 'Roles & permissions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static function permissionGroups(): array
    {
        $all = Permissions::all();
        $groups = [];
        foreach (Permissions::CONTENT as $key => $label) {
            $groups[$label] = collect($all)->filter(fn ($l, $p) => str_starts_with($p, $key.'.'))->map(fn ($l, $p) => ucfirst(str($p)->after('.')))->all();
        }
        $groups['Library'] = collect($all)->filter(fn ($l, $p) => str($p)->startsWith(['media.', 'taxonomy.', 'profile.', 'reusable_blocks.']))->all();
        $groups['Site management'] = Permissions::SYSTEM;

        return $groups;
    }

    public static function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (static::permissionGroups() as $label => $options) {
            $wide = in_array($label, ['Library', 'Site management'], true);
            $sections[] = Section::make($label)->compact()->columnSpan($wide ? 'full' : 1)->schema([
                CheckboxList::make('permissions_'.str($label)->slug('_'))
                    ->hiddenLabel()->options($options)->columns(['default' => 1, 'sm' => 2, 'lg' => $wide ? 3 : 5])->bulkToggleable()
                    ->afterStateHydrated(fn (CheckboxList $component, ?Role $record) => $component->state($record ? $record->permissions->pluck('name')->intersect(array_keys($options))->values()->all() : []))
                    ->dehydrated(false),
            ]);
        }

        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->required()->unique(ignoreRecord: true)->regex('/^[a-z_]+$/')
                    ->disabled(fn (?Role $record) => $record && array_key_exists($record->name, Permissions::ROLES))
                    ->helperText('Built-in roles cannot be renamed. Super administrators always have every permission.'),
            ]),
            ...$sections,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->formatStateUsing(fn ($state) => Permissions::ROLES[$state] ?? str($state)->headline())->weight('medium'),
            TextColumn::make('users_count')->counts('users')->label('Users'),
            TextColumn::make('permissions_count')->counts('permissions')->label('Permissions'),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->hidden(fn (Role $record) => array_key_exists($record->name, Permissions::ROLES)),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
