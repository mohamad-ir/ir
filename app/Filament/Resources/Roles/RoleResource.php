<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Concerns\TranslatesResourceLabels;
use App\Models\ActivityLog;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Role::class;

    protected static ?string $navigationLabel = 'Roles & permissions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    /** Permissions that decide who may enter the dashboard and who sees paid content for free. */
    public const ACCESS = ['dashboard.access', 'content.free_access'];

    protected static function permissionGroups(): array
    {
        $all = Permissions::all();
        $groups = ['Access' => collect(self::ACCESS)->mapWithKeys(fn ($p) => [$p => $all[$p]])->all()];
        foreach (Permissions::CONTENT as $key => $label) {
            $groups[$label] = collect($all)->filter(fn ($l, $p) => str_starts_with($p, $key.'.'))->map(fn ($l, $p) => ucfirst(str($p)->after('.')))->all();
        }
        $groups['Library'] = collect($all)->filter(fn ($l, $p) => str($p)->startsWith(['media.', 'taxonomy.', 'profile.', 'reusable_blocks.']))->all();
        $groups['Site management'] = array_diff_key(Permissions::SYSTEM, array_flip(self::ACCESS));

        return $groups;
    }

    public static function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (static::permissionGroups() as $label => $options) {
            $wide = in_array($label, ['Access', 'Library', 'Site management'], true);
            // Locked permissions (fixed for built-in roles, super-administrator-only for others) are not
            // offered as checkboxes; their current state is described instead and kept on save.
            $editable = fn (?Role $record) => collect($options)->reject(fn ($l, $p) => static::isLocked($p, $record));
            $sections[] = Section::make($label)->heading(__($label))->compact()->columnSpan($wide ? 'full' : 1)
                ->description($label === 'Access' ? fn (?Role $record) => static::accessDescription($record) : null)
                ->schema([
                    CheckboxList::make('permissions_'.str($label)->slug('_'))
                        ->hiddenLabel()->options(fn (?Role $record) => $editable($record)->map(fn ($l) => __($l))->all())
                        ->visible(fn (?Role $record) => $editable($record)->isNotEmpty())
                        ->columns(['default' => 1, 'sm' => 2, 'lg' => $wide ? ($label === 'Access' ? 2 : 3) : 5])->bulkToggleable($label !== 'Access')
                        ->afterStateHydrated(fn (CheckboxList $component, ?Role $record) => $component->state($record ? $record->permissions->pluck('name')->intersect($editable($record)->keys())->values()->all() : []))
                        ->dehydrated(false),
                ]);
        }

        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->required()->unique(ignoreRecord: true)->regex('/^[a-z_]+$/')
                    ->disabled(fn (?Role $record) => $record && array_key_exists($record->name, Permissions::ROLES))
                    ->helperText(__('Built-in roles cannot be renamed. Super administrators always have every permission.')),
            ]),
            ...$sections,
        ]);
    }

    /** Explanation of the Access section, including the state of permissions that cannot be changed here. */
    public static function accessDescription(?Role $role): string
    {
        $text = __('Without “Sign in to the dashboard”, users with this role cannot use any of the permissions below in the dashboard; they only use the public site. Two-step verification applies to everyone who signs in to the dashboard.');
        $fixed = [];
        if ($role && array_key_exists($role->name, Permissions::ROLES)) {
            $fixed[] = $role->name === 'member'
                ? __('Sign in to the dashboard: never (public accounts).')
                : __('Sign in to the dashboard: always (built-in team role).');
            $fixed[] = $role->name === 'member'
                ? __('Free access to paid content: only with an active membership or a purchase.')
                : __('Free access to paid content: always (built-in team role).');
        } elseif (! auth()->user()?->isSuperAdmin()) {
            $fixed[] = ($role?->permissions->contains('name', 'content.free_access') ? __('Free access to paid content: on.') : __('Free access to paid content: off.'))
                .' '.__('Only a super administrator can change it.');
        } else {
            $fixed[] = __('Free access to paid content is off by default; only super administrators see and change this option.');
        }

        return $text.' '.implode(' ', $fixed);
    }

    /** True when this permission cannot be changed here for this role by the current user. */
    public static function isLocked(string $permission, ?Role $role): bool
    {
        if ($role && array_key_exists($permission, Permissions::lockedFor($role->name))) {
            return true; // built-in roles have fixed access
        }

        return in_array($permission, Permissions::SUPER_ADMIN_ONLY, true) && ! auth()->user()?->isSuperAdmin();
    }

    /**
     * Saves the ticked permissions. Server-side guarantees, whatever the browser sends: the fixed
     * access of built-in roles, and super-administrator-only permissions kept unchanged for others.
     */
    public static function savePermissions(Role $role, array $rawState): void
    {
        $requested = collect($rawState)->filter(fn ($v, $k) => str_starts_with($k, 'permissions_'))->flatten()->filter()->unique()->values();
        $valid = array_keys(Permissions::all());
        $requested = $requested->intersect($valid);

        if (! auth()->user()?->isSuperAdmin()) {
            $current = $role->exists ? $role->permissions->pluck('name') : collect();
            $requested = $requested->diff(Permissions::SUPER_ADMIN_ONLY)->merge($current->intersect(Permissions::SUPER_ADMIN_ONLY));
        }

        $role->syncPermissions($requested->unique()->values()->all());
        Permissions::applyLocked($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ActivityLog::record('permissions', 'Permissions updated for role '.$role->name, $role);

        $role->load('permissions');
        $names = $role->permissions->pluck('name');
        if (! $names->contains('dashboard.access') && $names->diff(['content.free_access'])->isNotEmpty() && $role->name !== 'member') {
            Notification::make()->warning()->persistent()
                ->title(__('This role cannot sign in to the dashboard'))
                ->body(__('Its dashboard permissions have no effect until “Sign in to the dashboard” is ticked.'))
                ->send();
        }
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->formatStateUsing(fn ($state) => __(Permissions::ROLES[$state] ?? str($state)->headline()->toString()))->weight('medium'),
            TextColumn::make('users_count')->counts('users')->label(__('Users')),
            TextColumn::make('permissions_count')->counts('permissions')->label(__('Permissions')),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->hidden(fn (Role $record) => array_key_exists($record->name, Permissions::ROLES)
                || (! auth()->user()->isSuperAdmin() && $record->permissions->pluck('name')->intersect(Permissions::SUPER_ADMIN_ONLY)->isNotEmpty())),
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
