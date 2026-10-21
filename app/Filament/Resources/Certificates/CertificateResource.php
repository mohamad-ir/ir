<?php

namespace App\Filament\Resources\Certificates;

use App\Models\Certificate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'recipient_name'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('ID')->searchable()->copyable()->fontFamily('mono')->weight('medium'),
                TextColumn::make('recipient_name')->label('Awarded to')->searchable()->description(fn (Certificate $record) => $record->user?->email),
                TextColumn::make('course_title')->label('Course')->searchable()->wrap(),
                TextColumn::make('hours')->formatStateUsing(fn (Certificate $record) => $record->hoursLabel())->placeholder('—')->toggleable(),
                TextColumn::make('issued_at')->label('Issued')->date('j M Y')->sortable(),
                TextColumn::make('status')->badge()->getStateUsing(fn (Certificate $record) => $record->isValid() ? 'Valid' : 'Revoked')
                    ->color(fn (string $state) => $state === 'Valid' ? 'success' : 'danger')
                    ->description(fn (Certificate $record) => $record->revoke_reason),
            ])
            ->filters([
                SelectFilter::make('course_id')->label('Course')->relationship('course', 'title'),
                TernaryFilter::make('valid')->label('Status')->trueLabel('Valid')->falseLabel('Revoked')
                    ->queries(true: fn ($query) => $query->whereNull('revoked_at'), false: fn ($query) => $query->whereNotNull('revoked_at')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('verify')->label('Verification page')->icon('heroicon-o-check-badge')->url(fn (Certificate $record) => $record->verifyUrl(), shouldOpenInNewTab: true),
                    Action::make('pdf')->label('Download PDF')->icon('heroicon-o-arrow-down-tray')->url(fn (Certificate $record) => $record->downloadUrl(), shouldOpenInNewTab: true)
                        ->visible(fn (Certificate $record) => $record->isValid()),
                    Action::make('revoke')->label('Revoke')->icon('heroicon-o-no-symbol')->color('danger')
                        ->visible(fn (Certificate $record) => $record->isValid() && auth()->user()?->can('courses.update'))
                        ->schema([TextInput::make('reason')->required()->maxLength(250)->placeholder('Issued in error')])
                        ->modalDescription('The verification page will show the certificate as revoked. This can be undone.')
                        ->action(function (Certificate $record, array $data) {
                            $record->update(['revoked_at' => now(), 'revoke_reason' => $data['reason']]);
                            Notification::make()->success()->title('Certificate revoked')->send();
                        }),
                    Action::make('reinstate')->label('Reinstate')->icon('heroicon-o-arrow-uturn-left')->requiresConfirmation()
                        ->visible(fn (Certificate $record) => ! $record->isValid() && auth()->user()?->can('courses.update'))
                        ->action(fn (Certificate $record) => $record->update(['revoked_at' => null, 'revoke_reason' => null])),
                ]),
            ])
            ->emptyStateHeading('No certificates yet')
            ->emptyStateDescription('Certificates are issued automatically when a learner completes a course.');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCertificates::route('/')];
    }
}
