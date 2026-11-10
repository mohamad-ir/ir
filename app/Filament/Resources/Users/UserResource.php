<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\TranslatesResourceLabels;
use App\Filament\Support\InitialsAvatarProvider;
use App\Models\CareerStage;
use App\Models\User;
use App\Services\Users\AvatarService;
use App\Support\Countries;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Users & members';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'institution'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Account')->heading(__('Account'))->columns(2)->schema([
                    TextInput::make('title')->maxLength(40)->placeholder(__('Dr')),
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->columnSpanFull(),
                    TextInput::make('password')->password()->revealable()
                        ->rule(Password::min(10)->letters()->numbers())
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText(fn (string $operation) => $operation === 'edit' ? __('Leave empty to keep the current password.') : __('At least 10 characters with letters and numbers.')),
                    DateTimePicker::make('email_verified_at')->label(__('Email verified'))->native(false),
                ]),
                Section::make('Professional profile')->heading(__('Professional profile'))->columns(2)->schema([
                    Select::make('career_stage')->label(__('Career stage'))->options(fn (?User $record) => CareerStage::options(true, $record?->career_stage))->native(false),
                    TextInput::make('specialty'),
                    TextInput::make('institution'),
                    Select::make('country')->options(Countries::all())->searchable(),
                    Textarea::make('bio')->rows(3)->columnSpanFull()->helperText(__('Shown as the author biography on articles.')),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Access')->heading(__('Access'))->schema([
                    Select::make('roles')->relationship('roles', 'name')->multiple()->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => __(Permissions::ROLES[$record->name] ?? $record->name))
                        // Only a super administrator may give or take away the super administrator role and roles with free access to paid content.
                        // (roles the user already holds stay selectable so the rest of the form can be saved; the rule below refuses removing them)
                        ->disableOptionWhen(function (string $value, ?User $record) {
                            $restricted = once(fn () => Permissions::superAdminOnlyRoleIds());

                            return ! auth()->user()->isSuperAdmin() && in_array((int) $value, $restricted, true) && ! $record?->roles->contains('id', (int) $value);
                        })
                        ->rule(fn (?User $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                            if (auth()->user()->isSuperAdmin()) {
                                return;
                            }
                            $before = $record ? $record->roles()->pluck('id')->all() : [];
                            $changed = array_merge(array_diff(array_map('intval', (array) $value), $before), array_diff($before, array_map('intval', (array) $value)));
                            if (array_intersect($changed, Permissions::superAdminOnlyRoleIds())) {
                                $fail(__('Only a super administrator can give or remove this role.'));
                            }
                        })
                        ->helperText(__('Roles with “Sign in to the dashboard” (every built-in team role) can use this dashboard, with two-step verification. Members only use the public account area.')),
                    Toggle::make('is_active')->label(__('Account active'))->default(true)->helperText(__('Inactive users cannot sign in.')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('avatar')->label('')->circular()->imageSize(36)
                    ->getStateUsing(fn (User $record) => $record->avatarUrl('sm'))
                    ->defaultImageUrl(fn (User $record) => (new InitialsAvatarProvider)->get($record)),
                TextColumn::make('name')->searchable(['name', 'email'])->sortable()->weight('medium')->description(fn (User $record) => $record->email),
                TextColumn::make('roles.name')->label(__('Role'))->badge()->formatStateUsing(fn ($state) => __(Permissions::ROLES[$state] ?? $state))
                    ->color(fn ($state) => match ($state) {
                        'super_admin', 'administrator' => 'danger', 'editor' => 'warning', 'author', 'contributor' => 'info', default => 'gray'
                    }),
                TextColumn::make('career_stage')->label(__('Career stage'))->badge()->formatStateUsing(fn (?string $state) => CareerStage::labelFor($state))->color(fn (?string $state) => CareerStage::colorFor($state))->toggleable(),
                TextColumn::make('country')->formatStateUsing(fn ($state) => Countries::name($state))->toggleable(),
                IconColumn::make('two_factor')->label(__('2FA'))->getStateUsing(fn (User $record) => filled($record->app_authentication_secret))->boolean()->toggleable(),
                IconColumn::make('email_verified_at')->label(__('Verified'))->getStateUsing(fn (User $record) => $record->email_verified_at !== null)->boolean(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
                TextColumn::make('last_login_at')->label(__('Last sign-in'))->since()->sortable()->placeholder(__('Never')),
                TextColumn::make('created_at')->label(__('Joined'))->date('j M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')->relationship('roles', 'name')->getOptionLabelFromRecordUsing(fn ($record) => __(Permissions::ROLES[$record->name] ?? $record->name)),
                SelectFilter::make('career_stage')->label(__('Career stage'))->options(fn () => CareerStage::options(false)),
                TernaryFilter::make('staff')->label(__('Account type'))->trueLabel(__('Staff'))->falseLabel(__('Members'))
                    ->queries(true: fn (Builder $query) => $query->dashboardUsers(), false: fn (Builder $query) => $query->whereNotIn('id', User::dashboardUsers()->select('id'))),
                TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('reset')->label(__('Send password reset link'))->icon('heroicon-o-key')->requiresConfirmation()
                        ->action(function (User $record) {
                            PasswordBroker::sendResetLink(['email' => $record->email]);
                            Notification::make()->success()->title(__('Reset link sent to :email', ['email' => $record->email]))->send();
                        }),
                    Action::make('reset2fa')->label(__('Reset two-factor authentication'))->icon('heroicon-o-shield-exclamation')->color('warning')
                        ->visible(fn (User $record) => filled($record->app_authentication_secret) && auth()->user()->isSuperAdmin())
                        ->requiresConfirmation()->modalDescription(__('Use this if the user lost their authenticator device. They will be asked to set it up again at next sign-in.'))
                        ->action(function (User $record) {
                            $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                            Notification::make()->success()->title(__('Two-factor authentication reset'))->send();
                        }),
                    Action::make('removePhoto')->label(__('Remove profile photo'))->icon('heroicon-o-user-minus')->color('warning')
                        ->visible(fn (User $record) => filled($record->avatar_path))
                        ->requiresConfirmation()->modalDescription(__('Use this for an unsuitable photo. The member can upload a new one.'))
                        ->action(function (User $record) {
                            app(AvatarService::class)->remove($record);
                            Notification::make()->success()->title(__('Profile photo removed'))->send();
                        }),
                    DeleteAction::make(),
                ]),
            ])
            // Each selected user is checked against UserPolicy::delete (super administrators, own account).
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->authorizeIndividualRecords('delete')])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
