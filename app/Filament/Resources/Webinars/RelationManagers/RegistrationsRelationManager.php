<?php

namespace App\Filament\Resources\Webinars\RelationManagers;

use App\Models\User;
use App\Models\WebinarRegistration;
use App\Services\Learning\LearningService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Registrations';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->activeRegistrations()->count();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'order']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Name')->searchable()->description(fn (WebinarRegistration $record) => $record->user?->email),
                TextColumn::make('user.institution')->label('Institution')->toggleable()->placeholder('—'),
                TextColumn::make('source')->label('Place')->badge()->formatStateUsing(fn ($state) => WebinarRegistration::SOURCES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'purchase' => 'success', 'membership' => 'warning', 'admin' => 'info', default => 'gray' }),
                TextColumn::make('created_at')->label('Registered')->date('j M Y')->sortable(),
                TextColumn::make('joined_at')->label('Joined')->dateTime('j M H:i')->placeholder('—')->toggleable(),
                TextColumn::make('canceled_at')->label('Cancelled')->date('j M Y')->placeholder('—')->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Status')->default(true)->trueLabel('Active')->falseLabel('Cancelled')
                    ->queries(true: fn ($query) => $query->whereNull('canceled_at'), false: fn ($query) => $query->whereNotNull('canceled_at')),
            ])
            ->headerActions([
                Action::make('export')->label('Export CSV')->icon('heroicon-m-arrow-down-tray')->color('gray')
                    ->action(function () {
                        $rows = $this->getOwnerRecord()->activeRegistrations()->with('user')->get();

                        return response()->streamDownload(function () use ($rows) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['Name', 'Email', 'Institution', 'Country', 'Place', 'Registered', 'Joined']);
                            foreach ($rows as $r) {
                                fputcsv($out, [$r->user?->name, $r->user?->email, $r->user?->institution, $r->user?->country, $r->source, $r->created_at?->toDateTimeString(), $r->joined_at?->toDateTimeString()]);
                            }
                            fclose($out);
                        }, 'registrations-'.$this->getOwnerRecord()->slug.'.csv', ['Content-Type' => 'text/csv']);
                    }),
                Action::make('add')->label('Add participant')->icon('heroicon-m-user-plus')
                    ->visible(fn () => auth()->user()?->can('sales.manage') || auth()->user()?->can('webinars.update'))
                    ->schema([
                        Select::make('user_id')->label('User')->required()->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->limit(30)->get()->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})"]))
                            ->getOptionLabelUsing(fn ($value) => User::find($value)?->email),
                        Toggle::make('notify')->label('Send confirmation with calendar invitation')->default(true),
                    ])
                    ->action(function (array $data) {
                        app(LearningService::class)->register(User::findOrFail($data['user_id']), $this->getOwnerRecord(), 'admin', notify: (bool) $data['notify']);
                        Notification::make()->success()->title('Participant added')->send();
                    }),
            ])
            ->recordActions([
                Action::make('cancel')->label('Cancel place')->icon('heroicon-m-x-mark')->color('danger')->iconButton()->requiresConfirmation()
                    ->visible(fn (WebinarRegistration $record) => $record->canceled_at === null && (auth()->user()?->can('sales.manage') ?? false))
                    ->modalDescription('Paid tickets are not refunded automatically — refund the order under Sales › Orders if needed.')
                    ->action(fn (WebinarRegistration $record) => $record->update(['canceled_at' => now()])),
            ])
            ->emptyStateHeading('No registrations yet');
    }
}
