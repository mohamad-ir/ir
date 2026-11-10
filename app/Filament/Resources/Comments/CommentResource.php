<?php

namespace App\Filament\Resources\Comments;

use App\Models\Comment;
use App\Services\Comments\CommentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class CommentResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Comment::class;

    protected static ?string $navigationLabel = 'Comments';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $pending = Comment::where('status', 'pending')->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Awaiting approval';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $service = fn () => app(CommentService::class);

        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'commentable', 'parent.user']))
            ->columns([
                TextColumn::make('body')->label(__('Comment'))->searchable()->wrap()->width('40%')
                    ->formatStateUsing(fn (Comment $record) => new HtmlString(
                        ($record->parent ? '<span class="text-xs text-gray-500">↳ '.e(__('Reply to :name', ['name' => $record->parent->authorName()])).'</span><br>' : '')
                        .'<span class="text-sm">'.e($record->excerpt(220)).'</span>'
                        .($record->moderation_note ? '<br><span class="text-xs text-warning-600">'.e($record->moderation_note).'</span>' : '')))
                    ->description(fn (Comment $record) => $record->authorName().($record->user ? ' · '.$record->user->email : '')),
                TextColumn::make('commentable.title')->label(__('On'))->limit(60)->wrap()
                    ->description(fn (Comment $record) => __(['case' => 'OCT case', 'article' => 'Article', 'video' => 'Video'][$record->commentable_type] ?? $record->commentable_type))
                    ->url(fn (Comment $record) => $record->url(), shouldOpenInNewTab: true),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => __(Comment::STATUSES[$state] ?? $state))
                    ->color(fn (string $state) => ['pending' => 'warning', 'approved' => 'success', 'spam' => 'danger', 'rejected' => 'gray'][$state] ?? 'gray'),
                IconColumn::make('is_pinned')->label(__('Pinned'))->boolean()->trueIcon('heroicon-s-bookmark')->falseIcon('')->toggleable(),
                TextColumn::make('likes_count')->label(__('Helpful'))->numeric()->sortable()->toggleable(),
                TextColumn::make('reports_count')->label(__('Reports'))->numeric()->sortable()
                    ->color(fn (int $state) => $state ? 'danger' : 'gray'),
                TextColumn::make('created_at')->label(__('Posted'))->since()->sortable()->dateTimeTooltip(),
            ])
            ->filters([
                SelectFilter::make('commentable_type')->label(__('Content type'))->options(\App\Support\Ui::options(['case' => 'OCT cases', 'article' => 'Articles', 'video' => 'Videos'])),
                SelectFilter::make('user_id')->label(__('Member'))->relationship('user', 'name')->searchable(),
            ])
            ->recordActions([
                Action::make('approve')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Comment $record) => ! $record->isApproved())
                    ->action(fn (Comment $record) => $service()->approve($record, auth()->user())),
                Action::make('reply')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                    ->visible(fn (Comment $record) => $record->isApproved() && $record->commentable)
                    ->modalHeading(fn (Comment $record) => __('Reply to :name', ['name' => $record->authorName()]))
                    ->modalDescription(fn (Comment $record) => new HtmlString('<div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">'.$record->bodyHtml().'</div>'))
                    ->schema([Textarea::make('body')->label(__('Your reply'))->required()->rows(4)->maxLength(CommentService::MAX_LENGTH)])
                    ->modalSubmitActionLabel(__('Post reply'))
                    ->action(function (Comment $record, array $data) use ($service) {
                        $service()->post(auth()->user(), $record->commentable, $data['body'], $record);
                        Notification::make()->success()->title(__('Reply posted'))->send();
                    }),
                ActionGroup::make([
                    Action::make('view')->label(__('Read in full'))->icon('heroicon-o-eye')
                        ->modalHeading(fn (Comment $record) => __(':name on “:title”', ['name' => $record->authorName(), 'title' => $record->commentable?->title ?? '—']))
                        ->modalContent(fn (Comment $record) => new HtmlString('<div class="text-sm leading-relaxed">'.$record->bodyHtml().'</div>'
                            .self::reportsHtml($record)))
                        ->modalSubmitAction(false)->modalCancelActionLabel(__('Close')),
                    Action::make('pin')->label(fn (Comment $record) => $record->is_pinned ? 'Unpin' : 'Pin to top')->icon('heroicon-o-bookmark')
                        ->visible(fn (Comment $record) => ! $record->parent_id)
                        ->action(fn (Comment $record) => $record->forceFill(['is_pinned' => ! $record->is_pinned])->saveQuietly()),
                    Action::make('reject')->label(__('Hide (reject)'))->icon('heroicon-o-eye-slash')
                        ->visible(fn (Comment $record) => $record->status !== 'rejected')
                        ->action(fn (Comment $record) => $service()->setStatus($record, 'rejected')),
                    Action::make('spam')->label(__('Mark as spam'))->icon('heroicon-o-shield-exclamation')->color('danger')
                        ->visible(fn (Comment $record) => $record->status !== 'spam')
                        ->action(fn (Comment $record) => $service()->setStatus($record, 'spam')),
                    Action::make('clearReports')->label(__('Dismiss reports'))->icon('heroicon-o-flag')
                        ->visible(fn (Comment $record) => $record->reports_count > 0)
                        ->action(function (Comment $record) {
                            \Illuminate\Support\Facades\DB::table('comment_reports')->where('comment_id', $record->id)->delete();
                            $record->forceFill(['reports_count' => 0])->saveQuietly();
                        }),
                    Action::make('delete')->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                        ->modalDescription(__('Replies to this comment stay visible; the comment itself is replaced by “removed”.'))
                        ->action(fn (Comment $record) => $service()->delete($record, auth()->user())),
                ]),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('approve')->label(__('Approve'))->icon('heroicon-o-check-circle')->color('success')
                    ->action(fn (Collection $records) => $records->each(fn (Comment $c) => $service()->approve($c, auth()->user())))->deselectRecordsAfterCompletion(),
                BulkAction::make('reject')->label(__('Hide (reject)'))->icon('heroicon-o-eye-slash')
                    ->action(fn (Collection $records) => $records->each(fn (Comment $c) => $service()->setStatus($c, 'rejected')))->deselectRecordsAfterCompletion(),
                BulkAction::make('spam')->label(__('Mark as spam'))->icon('heroicon-o-shield-exclamation')->color('danger')
                    ->action(fn (Collection $records) => $records->each(fn (Comment $c) => $service()->setStatus($c, 'spam')))->deselectRecordsAfterCompletion(),
                BulkAction::make('delete')->label(__('Delete'))->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each(fn (Comment $c) => $service()->delete($c, auth()->user())))->deselectRecordsAfterCompletion(),
            ])]);
    }

    protected static function reportsHtml(Comment $record): string
    {
        $reports = \Illuminate\Support\Facades\DB::table('comment_reports')->where('comment_id', $record->id)->get();
        if ($reports->isEmpty()) {
            return '';
        }

        return '<div class="mt-4 border-t pt-3 text-xs text-gray-500"><strong>'.e(__('Reports')).':</strong> '
            .e($reports->countBy('reason')->map(fn ($n, $r) => __(Comment::REPORT_REASONS[$r] ?? $r).' ×'.$n)->implode(', ')).'</div>';
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListComments::route('/')];
    }
}
