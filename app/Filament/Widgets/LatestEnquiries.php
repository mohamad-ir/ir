<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Widgets\Widget;

class LatestEnquiries extends Widget
{
    protected string $view = 'filament.widgets.enquiries';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 4];

    public static function canView(): bool
    {
        return auth()->user()?->can('enquiries.manage') ?? false;
    }

    public function getViewData(): array
    {
        return ['enquiries' => Enquiry::where('status', '!=', 'archived')->latest()->limit(5)->get(), 'url' => fn ($e) => EnquiryResource::getUrl('edit', ['record' => $e]), 'all' => EnquiryResource::getUrl()];
    }
}
