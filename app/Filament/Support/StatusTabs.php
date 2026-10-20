<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Tabs\Tab;

trait StatusTabs
{
    protected function statusTabs(): array
    {
        $model = static::getResource()::getModel();
        $count = fn ($cb) => $cb($model::query())->count();

        return [
            'all' => Tab::make('All'),
            'published' => Tab::make('Published')->badge($count(fn ($query) => $query->published()))->modifyQueryUsing(fn ($query) => $query->published()),
            'scheduled' => Tab::make('Scheduled')->badge($count(fn ($query) => $query->where('status', 'published')->where('published_at', '>', now())) ?: null)
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'published')->where('published_at', '>', now())),
            'drafts' => Tab::make('Drafts')->badge($count(fn ($query) => $query->where('status', 'draft')) ?: null)->modifyQueryUsing(fn ($query) => $query->where('status', 'draft')),
            'pending' => Tab::make('In review')->badge($count(fn ($query) => $query->where('status', 'pending')) ?: null)->badgeColor('warning')->modifyQueryUsing(fn ($query) => $query->where('status', 'pending')),
        ];
    }
}
