<?php

namespace App\Filament\Support;

use App\Enums\ContentStatus;
use App\Models\Revision;
use App\Services\Search\SearchIndexer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared behaviour for create/edit pages of editorial content:
 * permission-aware publishing, search re-indexing after relations are
 * saved, preview links and revision history with restore.
 */
trait InteractsWithContent
{
    abstract protected static function previewType(): string;

    abstract protected static function permissionPrefix(): string;

    protected function enforcePublishingPermission(array $data): array
    {
        $record = $this->record ?? null;
        $wasPublished = $record instanceof Model && $record->status === ContentStatus::Published;

        if (($data['status'] ?? null) === ContentStatus::Published->value && ! $wasPublished && ! auth()->user()->can(static::permissionPrefix().'.publish')) {
            $data['status'] = ContentStatus::Pending->value;
            Notification::make()->warning()->title('Submitted for review')->body('You do not have permission to publish, so this item is waiting for an editor.')->send();
        }

        if (! auth()->user()->can('content.edit_others') && array_key_exists('author_id', $data)) {
            $data['author_id'] = $record?->author_id ?? auth()->id();
        }

        return $data;
    }

    protected function reindex(): void
    {
        if (method_exists($this->record, 'toSearchDocument')) {
            app(SearchIndexer::class)->sync($this->record->fresh());
        }
    }

    protected function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn () => route('preview.show', [static::previewType(), $this->record->getKey()]), shouldOpenInNewTab: true);
    }

    protected function viewLiveAction(): Action
    {
        return Action::make('viewLive')
            ->label('View live')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('gray')
            ->visible(fn () => method_exists($this->record, 'isPublished') && $this->record->isPublished())
            ->url(fn () => $this->record->url(), shouldOpenInNewTab: true);
    }

    protected function revisionsAction(): Action
    {
        return Action::make('revisions')
            ->label(fn () => 'History ('.$this->record->revisions()->count().')')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalWidth('2xl')
            ->modalHeading('Revision history')
            ->modalDescription('Every save is stored. Compare versions and restore any of them — restoring creates a new revision, so nothing is lost.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view('filament.revisions', [
                'revisions' => $this->record->revisions()->with('user')->limit(50)->get(),
                'current' => $this->record->revisionSnapshot(),
            ]));
    }

    public function restoreRevision(int $revisionId): void
    {
        abort_unless(auth()->user()->can('update', $this->record), 403);
        $revision = Revision::where('revisionable_type', $this->record->getMorphClass())
            ->where('revisionable_id', $this->record->getKey())->findOrFail($revisionId);

        $this->record->restoreRevision($revision);
        $this->reindex();
        $this->fillForm();
        $this->unmountAction();

        Notification::make()->success()->title('Revision restored')->body('The version from '.$revision->created_at->format('j M Y H:i').' is now current.')->send();
    }
}
