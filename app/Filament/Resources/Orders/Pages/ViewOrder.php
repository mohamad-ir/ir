<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [OrderResource::refundAction()->after(fn () => $this->refreshFormData(['status', 'refunded_amount', 'refunded_at']))];
    }
}
