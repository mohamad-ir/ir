<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Pages\Settings\CommentSettings;
use App\Filament\Resources\Comments\CommentResource;
use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListComments extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')->label(__('Comment settings'))->icon('heroicon-o-cog-6-tooth')->color('gray')
                ->url(CommentSettings::getUrl())->visible(fn () => CommentSettings::canAccess()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Awaiting approval')->label(__('Awaiting approval'))->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'))
                ->badge(Comment::where('status', 'pending')->count() ?: null)->badgeColor('warning'),
            'reported' => Tab::make('Reported')->label(__('Reported'))->modifyQueryUsing(fn ($query) => $query->where('reports_count', '>', 0)->whereIn('status', ['approved', 'pending']))
                ->badge(Comment::where('reports_count', '>', 0)->whereIn('status', ['approved', 'pending'])->count() ?: null)->badgeColor('danger'),
            'approved' => Tab::make('Published')->label(__('Published'))->modifyQueryUsing(fn ($query) => $query->where('status', 'approved')),
            'spam' => Tab::make('Spam')->label(__('Spam'))->modifyQueryUsing(fn ($query) => $query->where('status', 'spam')),
            'rejected' => Tab::make('Hidden')->label(__('Hidden'))->modifyQueryUsing(fn ($query) => $query->where('status', 'rejected')),
            'all' => Tab::make('All')->label(__('All')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return Comment::where('status', 'pending')->exists() ? 'pending' : 'approved';
    }
}
