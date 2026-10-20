<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    public function getTabs(): array
    {
        return [
            'inbox' => Tab::make('Inbox')->modifyQueryUsing(fn ($query) => $query->where('status', '!=', 'archived'))
                ->badge(Enquiry::where('status', 'new')->count() ?: null)->badgeColor('danger'),
            'replied' => Tab::make('Replied')->modifyQueryUsing(fn ($query) => $query->where('status', 'replied')),
            'archived' => Tab::make('Archived')->modifyQueryUsing(fn ($query) => $query->where('status', 'archived')),
        ];
    }
}
