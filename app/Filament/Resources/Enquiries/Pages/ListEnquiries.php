<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListEnquiries extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = EnquiryResource::class;

    public function getTabs(): array
    {
        return [
            'inbox' => Tab::make('Inbox')->label(__('Inbox'))->modifyQueryUsing(fn ($query) => $query->where('status', '!=', 'archived'))
                ->badge(Enquiry::where('status', 'new')->count() ?: null)->badgeColor('danger'),
            'replied' => Tab::make('Replied')->label(__('Replied'))->modifyQueryUsing(fn ($query) => $query->where('status', 'replied')),
            'archived' => Tab::make('Archived')->label(__('Archived'))->modifyQueryUsing(fn ($query) => $query->where('status', 'archived')),
        ];
    }
}
