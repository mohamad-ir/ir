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
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')->label('Comment settings')->icon('heroicon-o-cog-6-tooth')->color('gray')
                ->url(CommentSettings::getUrl())->visible(fn () => CommentSettings::canAccess()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Awaiting approval')->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'))
                ->badge(Comment::where('status', 'pending')->count() ?: null)->badgeColor('warning'),
            'reported' => Tab::make('Reported')->modifyQueryUsing(fn ($query) => $query->where('reports_count', '>', 0)->whereIn('status', ['approved', 'pending']))
                ->badge(Comment::where('reports_count', '>', 0)->whereIn('status', ['approved', 'pending'])->count() ?: null)->badgeColor('danger'),
            'approved' => Tab::make('Published')->modifyQueryUsing(fn ($query) => $query->where('status', 'approved')),
            'spam' => Tab::make('Spam')->modifyQueryUsing(fn ($query) => $query->where('status', 'spam')),
            'rejected' => Tab::make('Hidden')->modifyQueryUsing(fn ($query) => $query->where('status', 'rejected')),
            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return Comment::where('status', 'pending')->exists() ? 'pending' : 'approved';
    }
}
