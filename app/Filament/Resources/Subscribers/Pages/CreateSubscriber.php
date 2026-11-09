<?php

namespace App\Filament\Resources\Subscribers\Pages;

use App\Filament\Resources\Subscribers\SubscriberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriber extends CreateRecord
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = SubscriberResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
