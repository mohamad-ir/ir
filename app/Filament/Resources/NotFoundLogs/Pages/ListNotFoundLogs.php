<?php

namespace App\Filament\Resources\NotFoundLogs\Pages;

use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use Filament\Resources\Pages\ListRecords;

class ListNotFoundLogs extends ListRecords
{
    protected static string $resource = NotFoundLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Addresses visitors or search engines requested that do not exist. Redirect the important ones to keep traffic and rankings.';
    }
}
