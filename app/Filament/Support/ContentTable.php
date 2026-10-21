<?php

namespace App\Filament\Support;

use App\Enums\ContentStatus;
use App\Models\Topic;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentTable
{
    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(fn ($state, Model $record) => $record->displayStatus())
            ->color(fn (Model $record) => $record->displayStatusColor())
            ->description(fn (Model $record) => $record->isScheduled() ? $record->published_at->format('j M Y H:i') : null)
            ->sortable();
    }

    public static function published(): TextColumn
    {
        return TextColumn::make('published_at')->label('Published')->date('j M Y')->sortable()->toggleable()->placeholder('—');
    }

    public static function author(): TextColumn
    {
        return TextColumn::make('author.name')->label('Author')->toggleable(isToggledHiddenByDefault: true);
    }

    public static function views(): TextColumn
    {
        return TextColumn::make('views_count')->label('Views')->numeric()->sortable()->toggleable()->alignEnd();
    }

    public static function filters(bool $topics = true, bool $trashed = true): array
    {
        return array_values(array_filter([
            SelectFilter::make('status')->options(ContentStatus::options()),
            $topics ? SelectFilter::make('topic')->label('Topic')
                ->options(fn () => Topic::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->query(fn (Builder $query, array $data) => $data['value'] ? $query->inTopic((int) $data['value']) : $query) : null,
            SelectFilter::make('author_id')->label('Author')->options(fn () => User::role(User::STAFF_ROLES)->pluck('name', 'id')),
            $trashed ? TrashedFilter::make()->label('Bin') : null,
        ]));
    }

    public static function recordActions(string $previewType, string $permissionPrefix): array
    {
        return [
            EditAction::make()->iconButton()->tooltip('Edit'),
            ActionGroup::make([
                Action::make('preview')->label('Preview')->icon('heroicon-o-eye')
                    ->url(fn (Model $record) => route('preview.show', [$previewType, $record->getKey()]), shouldOpenInNewTab: true),
                Action::make('live')->label('View on website')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Model $record) => $record->url(), shouldOpenInNewTab: true)
                    ->visible(fn (Model $record) => method_exists($record, 'isPublished') && $record->isPublished() && method_exists($record, 'url')),
                ReplicateAction::make()->label('Duplicate as draft')
                    ->excludeAttributes(['views_count', 'reveals_count', 'guesses_count', 'correct_guesses_count', 'plays_count', 'completions_count', 'attempts_count', 'correct_count'])
                    ->mutateRecordDataUsing(function (array $data) {
                        $data['title'] = ($data['title'] ?? 'Copy').' (copy)';
                        if (isset($data['slug'])) {
                            $data['slug'] = Str::limit($data['slug'], 180, '').'-copy-'.Str::lower(Str::random(4));
                        }
                        $data['status'] = ContentStatus::Draft->value;
                        $data['published_at'] = null;
                        $data['author_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function (Model $replica, Model $record) {
                        if (method_exists($record, 'topics')) {
                            $replica->topics()->sync($record->topics->pluck('id'));
                        }
                        if (method_exists($record, 'modalities')) {
                            $replica->modalities()->sync($record->modalities->pluck('id'));
                        }
                    })
                    ->successRedirectUrl(fn (Model $replica) => request()->header('referer') ?? url()->previous()),
                DeleteAction::make()->label('Move to bin'),
                RestoreAction::make(),
                ForceDeleteAction::make()->label('Delete permanently'),
            ])->tooltip('More'),
        ];
    }

    public static function bulkActions(string $permissionPrefix): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('publish')->label('Publish')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn () => auth()->user()?->can($permissionPrefix.'.publish'))
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(fn (Model $record) => auth()->user()->can('update', $record) ? $record->update(['status' => ContentStatus::Published, 'published_at' => $record->published_at ?? now()]) : null);
                        Notification::make()->success()->title('Published '.$records->count().' items')->send();
                    })->deselectRecordsAfterCompletion(),
                BulkAction::make('unpublish')->label('Revert to draft')->icon('heroicon-o-pencil-square')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(fn (Model $record) => auth()->user()->can('update', $record) ? $record->update(['status' => ContentStatus::Draft]) : null);
                        Notification::make()->success()->title('Moved to drafts')->send();
                    })->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()->label('Move to bin'),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]),
        ];
    }
}
