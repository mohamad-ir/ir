<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Pages\Settings\CertificateSettings;
use App\Filament\Resources\Certificates\CertificateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListCertificates extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('settings')->label(__('Certificate design'))->icon('heroicon-o-paint-brush')->color('gray')->url(CertificateSettings::getUrl())];
    }
}
