<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\Learning\LearningService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-user-group';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('sales.view') ?? false;
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
        $learning = app(LearningService::class);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'order']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Student')->searchable()->description(fn (Enrollment $record) => $record->user?->email),
                TextColumn::make('source')->label('Access')->badge()->formatStateUsing(fn ($state) => Enrollment::SOURCES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'purchase' => 'success', 'membership' => 'warning', 'admin' => 'info', default => 'gray' }),
                TextColumn::make('progress')->label('Progress')->alignEnd()
                    ->getStateUsing(fn (Enrollment $record) => $record->user ? $learning->progress($record->user, $this->getOwnerRecord()).'%' : '—'),
                TextColumn::make('created_at')->label('Enrolled')->date('j M Y')->sortable(),
                TextColumn::make('completed_at')->label('Completed')->date('j M Y')->placeholder('—')->sortable(),
                TextColumn::make('certificate')->label('Certificate')->placeholder('—')
                    ->getStateUsing(fn (Enrollment $record) => \App\Models\Certificate::where('user_id', $record->user_id)->where('course_id', $record->course_id)->value('code')),
                TextColumn::make('order.number')->label('Order')->placeholder('—')->toggleable(),
            ])
            ->filters([SelectFilter::make('source')->options(Enrollment::SOURCES)])
            ->headerActions([
                Action::make('grant')->label('Give access')->icon('heroicon-m-user-plus')
                    ->visible(fn () => auth()->user()?->can('sales.manage'))
                    ->schema([
                        Select::make('user_id')->label('User')->required()->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->limit(30)->get()->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})"]))
                            ->getOptionLabelUsing(fn ($value) => User::find($value)?->email),
                        Toggle::make('notify')->label('Send enrolment email')->default(true),
                    ])
                    ->action(function (array $data) use ($learning) {
                        $learning->enroll(User::findOrFail($data['user_id']), $this->getOwnerRecord(), 'admin', notify: (bool) $data['notify']);
                        Notification::make()->success()->title('Access granted')->send();
                    }),
            ])
            ->recordActions([
                Action::make('certificate')->label('Issue certificate')->icon('heroicon-o-trophy')->iconButton()->requiresConfirmation()
                    ->visible(fn (Enrollment $record) => $this->getOwnerRecord()->certificate_enabled && $record->user && auth()->user()?->can('courses.update')
                        && ! \App\Models\Certificate::where('user_id', $record->user_id)->where('course_id', $record->course_id)->exists())
                    ->modalDescription('Issues the certificate now, even if not every lesson is marked complete, and emails it to the learner.')
                    ->action(function (Enrollment $record) {
                        app(\App\Services\Learning\CertificateService::class)->issue($record->user, $this->getOwnerRecord());
                        Notification::make()->success()->title('Certificate issued')->send();
                    }),
                DeleteAction::make()->label('Remove access')->iconButton()->visible(fn () => auth()->user()?->can('sales.manage'))
                    ->modalDescription('The student loses access to this course (members keep access through their membership). Payments are not refunded automatically.'),
            ])
            ->emptyStateHeading('No students yet');
    }
}
