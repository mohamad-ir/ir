<?php

namespace App\Filament\Resources\Users;

use App\Enums\CareerStage;
use App\Models\User;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
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
                Section::make('Account')->columns(2)->schema([
                    TextInput::make('title')->maxLength(40)->placeholder('Dr'),
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->columnSpanFull(),
                    TextInput::make('password')->password()->revealable()
                        ->rule(Password::min(10)->letters()->numbers())
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep the current password.' : 'At least 10 characters with letters and numbers.'),
                    DateTimePicker::make('email_verified_at')->label('Email verified')->native(false),
                ]),
                Section::make('Professional profile')->columns(2)->schema([
                    Select::make('career_stage')->options(CareerStage::options()),
                    TextInput::make('specialty'),
                    TextInput::make('institution'),
                    Select::make('country')->options(Countries::all())->searchable(),
                    Textarea::make('bio')->rows(3)->columnSpanFull()->helperText('Shown as the author biography on articles.'),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Access')->schema([
                    Select::make('roles')->relationship('roles', 'name')->multiple()->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => Permissions::ROLES[$record->name] ?? $record->name)
                        ->disableOptionWhen(fn (string $value, $label) => $label === Permissions::ROLES['super_admin'] && ! auth()->user()->isSuperAdmin())
                        ->helperText('Staff roles can sign in to this dashboard. Members can only use the public account area.'),
                    Toggle::make('is_active')->label('Account active')->default(true)->helperText('Inactive users cannot sign in.'),
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
                TextColumn::make('name')->searchable(['name', 'email'])->sortable()->weight('medium')->description(fn (User $record) => $record->email),
                TextColumn::make('roles.name')->label('Role')->badge()->formatStateUsing(fn ($state) => Permissions::ROLES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'super_admin', 'administrator' => 'danger', 'editor' => 'warning', 'author', 'contributor' => 'info', default => 'gray' }),
                TextColumn::make('career_stage')->badge()->color('gray')->toggleable(),
                TextColumn::make('country')->formatStateUsing(fn ($state) => Countries::name($state))->toggleable(),
                IconColumn::make('two_factor')->label('2FA')->getStateUsing(fn (User $record) => filled($record->app_authentication_secret))->boolean()->toggleable(),
                IconColumn::make('email_verified_at')->label('Verified')->getStateUsing(fn (User $record) => $record->email_verified_at !== null)->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('last_login_at')->label('Last sign-in')->since()->sortable()->placeholder('Never'),
                TextColumn::make('created_at')->label('Joined')->date('j M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')->relationship('roles', 'name')->getOptionLabelFromRecordUsing(fn ($record) => Permissions::ROLES[$record->name] ?? $record->name),
                SelectFilter::make('career_stage')->options(CareerStage::options()),
                TernaryFilter::make('staff')->label('Account type')->trueLabel('Staff')->falseLabel('Members')
                    ->queries(true: fn (Builder $query) => $query->role(User::STAFF_ROLES), false: fn (Builder $query) => $query->withoutRole(User::STAFF_ROLES)),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('reset')->label('Send password reset link')->icon('heroicon-o-key')->requiresConfirmation()
                        ->action(function (User $record) {
                            PasswordBroker::sendResetLink(['email' => $record->email]);
                            Notification::make()->success()->title('Reset link sent to '.$record->email)->send();
                        }),
                    Action::make('reset2fa')->label('Reset two-factor authentication')->icon('heroicon-o-shield-exclamation')->color('warning')
                        ->visible(fn (User $record) => filled($record->app_authentication_secret) && auth()->user()->isSuperAdmin())
                        ->requiresConfirmation()->modalDescription('Use this if the user lost their authenticator device. They will be asked to set it up again at next sign-in.')
                        ->action(function (User $record) {
                            $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                            Notification::make()->success()->title('Two-factor authentication reset')->send();
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
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
